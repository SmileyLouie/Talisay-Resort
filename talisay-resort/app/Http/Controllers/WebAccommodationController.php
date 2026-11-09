<?php

namespace App\Http\Controllers;

use App\Models\AccommodationUnit;
use App\Models\AmenityItem;
use App\Models\AuditLog;
use App\Models\UnitVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WebAccommodationController extends WebControllers
{
    // ─── Admin/Staff: Rooms and cottages are separate lists ───────────
    public function index(Request $request)
    {
        if ($request->query('filter') === 'cottages') {
            $params = [];
            $variant = (string) $request->query('variant');
            if (in_array($variant, array_merge(['normal', 'premium'], UnitVariant::activeSlugs('cottage')), true)) {
                $params['variant'] = $variant;
            }

            return redirect()->route('accommodations.cottages', $params);
        }

        return $this->unitManagement($request, 'room');
    }

    public function cottages(Request $request)
    {
        return $this->unitManagement($request, 'cottage');
    }

    private function unitManagement(Request $request, string $kind)
    {
        $unitVariants = UnitVariant::forKind($kind);
        $variant = $request->query('variant', 'all');
        $allowed = array_merge(['all', 'normal', 'premium'], $unitVariants->pluck('slug')->all());
        if (! in_array($variant, $allowed, true)) {
            $variant = 'all';
        }

        $scoped = AccommodationUnit::query()
            ->where('unit_type', $kind)
            ->orderBy('sort_order')
            ->orderBy('unit_number');

        $query = clone $scoped;
        if ($variant === 'normal') {
            $query->normal();
        } elseif ($variant === 'premium') {
            $query->premium();
        } elseif ($variant !== 'all') {
            $query->where('variant', $variant);
        }

        $units = $query->get();
        $variantCards = $unitVariants->map(fn ($variant) => [
            'slug'  => $variant->slug,
            'label' => $variant->label,
            'count' => (clone $scoped)->where('variant', $variant->slug)->count(),
        ])->values();
        $stats = [
            'total'     => (clone $scoped)->count(),
            'available' => (clone $scoped)->available()->count(),
        ];
        $amenityItems = AmenityItem::catalog();
        $takenNumbers = $this->takenUnitNumbers($kind);
        $nextNumber = $this->nextUnitNumber($takenNumbers);
        $numberPrefix = self::unitNumberPrefix($kind);
        $unitNumberMap = (clone $scoped)->pluck('unit_number', 'id')
            ->map(fn ($name) => self::trailingNumber((string) $name))
            ->filter()
            ->map(fn ($number, $id) => ['id' => $id, 'number' => $number])
            ->values();

        return view('accommodations.index', compact(
            'units', 'variant', 'stats', 'variantCards', 'kind', 'unitVariants', 'amenityItems',
            'nextNumber', 'numberPrefix', 'unitNumberMap'
        ));
    }

    private function redirectToUnitList(AccommodationUnit $unit)
    {
        $route = $unit->unit_type === 'cottage' ? 'accommodations.cottages' : 'accommodations.index';

        return redirect()->route($route);
    }

    // ─── Admin: Store new unit ────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_number'       => 'required|string|max:50',
            'unit_type'         => 'required|in:room,cottage',
            'variant'           => ['required', Rule::in($this->variantSlugs($request->input('unit_type')))],
            'floor_area_sqm'    => 'nullable|numeric|min:1',
            'bed_configuration' => 'nullable|string|max:100',
            'max_occupancy'     => 'required|integer|min:1',
            'amenities'         => 'nullable|string',
            'description'       => 'nullable|string',
            'price_per_night'   => 'required|numeric|min:0',
            'price_per_hour'    => 'required|numeric|min:0',
            'tour_video'        => 'nullable|file|mimes:mp4,webm,ogv,mov,qt,m4v|max:204800',
            'images.*'          => 'nullable|image|max:5120',
            'sort_order'        => 'nullable|integer',
            'is_available'      => 'nullable|boolean',
        ], $this->unitValidationMessages());

        $data['unit_number'] = trim($data['unit_number']);
        if (ctype_digit($data['unit_number'])) {
            $data['unit_number'] = self::unitNumberPrefix($data['unit_type']).' '.(int) $data['unit_number'];
        }
        $this->ensureUnitNameIsFree($data['unit_type'], $data['unit_number']);

        // Parse amenities (newline-separated string → array)
        $amenitiesArray = [];
        if (!empty($data['amenities'])) {
            $amenitiesArray = array_values(array_filter(
                array_map('trim', explode("\n", $data['amenities']))
            ));
        }

        // Handle tour video upload
        $tourVideoPath = null;
        if ($request->hasFile('tour_video')) {
            $tourVideoPath = $request->file('tour_video')->store('accommodation-tours', 'public');
        }

        // Handle images upload
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $imagePaths[] = $img->store('accommodation-images', 'public');
            }
        }

        $unit = AccommodationUnit::create([
            'unit_number'       => $data['unit_number'],
            'unit_type'         => $data['unit_type'],
            'variant'           => $data['variant'],
            'floor_area_sqm'    => filled($data['floor_area_sqm'] ?? null) ? $data['floor_area_sqm'] : null,
            'bed_configuration' => $data['bed_configuration'] ?? null,
            'max_occupancy'     => $data['max_occupancy'],
            'amenities'         => $amenitiesArray,
            'description'       => $data['description'] ?? null,
            'images'            => $imagePaths,
            'tour_video_path'   => $tourVideoPath,
            'price_per_night'   => $data['price_per_night'],
            'price_per_hour'    => $data['price_per_hour'],
            'is_available'      => $request->boolean('is_available', true),
            'sort_order'        => $data['sort_order'] ?? 0,
        ]);

        AuditLog::log('accommodation_unit_created', $unit, null, $unit->toArray());
        app(\App\Services\SupabaseSyncService::class)->pushUnit($unit);

        return $this->redirectToUnitList($unit)
            ->with('success', "Unit {$unit->unit_number} created successfully!");
    }

    // ─── Admin: Update unit ───────────────────────────────────────────
    public function update(Request $request, AccommodationUnit $accommodation)
    {
        $data = $request->validate([
            'unit_number'       => 'required|string|max:50',
            'unit_type'         => 'required|in:room,cottage',
            'variant'           => ['required', Rule::in($this->variantSlugs($request->input('unit_type'), $accommodation->variant))],
            'floor_area_sqm'    => 'nullable|numeric|min:1',
            'bed_configuration' => 'nullable|string|max:100',
            'max_occupancy'     => 'required|integer|min:1',
            'amenities'         => 'nullable|string',
            'description'       => 'nullable|string',
            'price_per_night'   => 'required|numeric|min:0',
            'price_per_hour'    => 'required|numeric|min:0',
            'tour_video'        => 'nullable|file|mimes:mp4,webm,ogv,mov,qt,m4v|max:204800',
            'images.*'          => 'nullable|image|max:5120',
            'sort_order'        => 'nullable|integer',
            'is_available'      => 'nullable|boolean',
        ], $this->unitValidationMessages());

        $data['unit_number'] = trim($data['unit_number']);
        if ($data['unit_number'] !== $accommodation->unit_number || $data['unit_type'] !== $accommodation->unit_type) {
            $this->ensureUnitNameIsFree($data['unit_type'], $data['unit_number'], $accommodation->id);
        }

        $amenitiesArray = [];
        if (!empty($data['amenities'])) {
            $amenitiesArray = array_values(array_filter(
                array_map('trim', explode("\n", $data['amenities']))
            ));
        }

        $tourVideoPath = $accommodation->tour_video_path;
        if ($request->hasFile('tour_video')) {
            if ($tourVideoPath) Storage::disk('public')->delete($tourVideoPath);
            $tourVideoPath = $request->file('tour_video')->store('accommodation-tours', 'public');
        }

        $imagePaths = $accommodation->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $imagePaths[] = $img->store('accommodation-images', 'public');
            }
        }

        $old = $accommodation->toArray();
        $accommodation->update([
            'unit_number'       => $data['unit_number'],
            'unit_type'         => $data['unit_type'],
            'variant'           => $data['variant'],
            'floor_area_sqm'    => filled($data['floor_area_sqm'] ?? null) ? $data['floor_area_sqm'] : null,
            'bed_configuration' => $data['bed_configuration'] ?? null,
            'max_occupancy'     => $data['max_occupancy'],
            'amenities'         => $amenitiesArray,
            'description'       => $data['description'] ?? null,
            'images'            => $imagePaths,
            'tour_video_path'   => $tourVideoPath,
            'price_per_night'   => $data['price_per_night'],
            'price_per_hour'    => $data['price_per_hour'],
            'is_available'      => $request->boolean('is_available', true),
            'sort_order'        => $data['sort_order'] ?? 0,
        ]);

        AuditLog::log('accommodation_unit_updated', $accommodation, $old, $accommodation->toArray());
        app(\App\Services\SupabaseSyncService::class)->pushUnit($accommodation);

        return $this->redirectToUnitList($accommodation)
            ->with('success', "Unit {$accommodation->unit_number} updated successfully!");
    }

    // ─── Admin: Delete unit ───────────────────────────────────────────
    public function destroy(AccommodationUnit $accommodation)
    {
        $upcoming = $accommodation->bookings()
            ->whereIn('status', \App\Models\Booking::ACTIVE_STATUSES)
            ->get()
            ->reject(fn ($booking) => $booking->stayHasEnded());
        if ($upcoming->isNotEmpty()) {
            $refs = $upcoming->map(fn ($booking) => $booking->reference_no ?: '#'.$booking->id)->take(3)->implode(', ');
            $count = $upcoming->count();

            return $this->redirectToUnitList($accommodation)
                ->with('error', "{$accommodation->unit_number} cannot be deleted while it has {$count} upcoming or ongoing ".($count === 1 ? 'reservation' : 'reservations')." ({$refs}). Cancel or finish them first, or mark the unit unavailable.");
        }

        $name = $accommodation->unit_number;
        if ($accommodation->tour_video_path) {
            Storage::disk('public')->delete($accommodation->tour_video_path);
        }
        $mysqlId = $accommodation->id;
        try {
            $accommodation->delete();
        } catch (\Throwable $e) {
            report($e);

            return $this->redirectToUnitList($accommodation)
                ->with('error', "{$name} could not be deleted because a reservation is still linked to it.");
        }

        AuditLog::log('accommodation_unit_deleted', $accommodation);
        app(\App\Services\SupabaseSyncService::class)->deleteUnit($mysqlId);

        return $this->redirectToUnitList($accommodation)
            ->with('success', "Unit {$name} deleted.");
    }

    // ─── Admin: Toggle availability ───────────────────────────────────
    public function toggleAvailability(AccommodationUnit $accommodation)
    {
        $accommodation->update(['is_available' => !$accommodation->is_available]);
        app(\App\Services\SupabaseSyncService::class)->pushUnit($accommodation->fresh());

        $status = $accommodation->is_available ? 'available' : 'unavailable';
        return $this->redirectToUnitList($accommodation)
            ->with('success', "{$accommodation->unit_number} is now {$status}.");
    }

    // ─── Tourist: Browse all accommodations ───────────────────────────
    public function guestIndex(Request $request)
    {
        $filter = $request->filter ?? 'all';
        $variant = $request->variant ?? 'all';

        if ($filter === 'normal') {
            $variant = 'normal';
            $filter = 'all';
        } elseif ($filter === 'premium') {
            $variant = 'premium';
            $filter = 'all';
        }

        if (! in_array($filter, ['all', 'rooms', 'cottages'], true)) {
            $filter = 'all';
        }
        if (! in_array($variant, ['all', 'normal', 'premium'], true)) {
            $variant = 'all';
        }

        $query = AccommodationUnit::available()->orderBy('unit_type')->orderBy('sort_order');
        if ($filter === 'rooms') $query->rooms();
        elseif ($filter === 'cottages') $query->cottages();
        if ($variant === 'normal') $query->normal();
        elseif ($variant === 'premium') $query->premium();

        $units = $query->get();

        $inCategory = AccommodationUnit::available();
        if ($filter === 'rooms') $inCategory->rooms();
        elseif ($filter === 'cottages') $inCategory->cottages();

        $counts = [
            'all' => AccommodationUnit::available()->count(),
            'rooms' => AccommodationUnit::available()->rooms()->count(),
            'cottages' => AccommodationUnit::available()->cottages()->count(),
            'standard' => (clone $inCategory)->normal()->count(),
            'deluxe' => (clone $inCategory)->premium()->count(),
        ];

        return view('tourist.accommodations', compact('units', 'filter', 'variant', 'counts'));
    }

    // ─── Tourist: View individual unit detail ─────────────────────────
    public function guestDetail(AccommodationUnit $unit)
    {
        // Load similar units for "You may also like" section
        $similar = AccommodationUnit::available()
            ->where('id', '!=', $unit->id)
            ->where('unit_type', $unit->unit_type)
            ->orderBy('sort_order')
            ->take(3)
            ->get();

        return view('tourist.accommodation-detail', compact('unit', 'similar'));
    }

    private function variantSlugs(?string $kind, ?string $current = null): array
    {
        $slugs = UnitVariant::activeSlugs($kind ?: 'room');
        if ($current && ! in_array($current, $slugs, true)) {
            $slugs[] = $current;
        }

        return $slugs ?: ['normal', 'premium'];
    }

    public static function unitNumberPrefix(string $kind): string
    {
        return $kind === 'cottage' ? 'COTTAGE' : 'ROOM';
    }

    private static function trailingNumber(string $name): ?int
    {
        return preg_match('/(\d+)\s*$/', $name, $match) ? (int) $match[1] : null;
    }

    private function takenUnitNumbers(string $kind, ?int $ignoreId = null): array
    {
        return AccommodationUnit::query()
            ->where('unit_type', $kind)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->pluck('unit_number')
            ->map(fn ($name) => self::trailingNumber((string) $name))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function nextUnitNumber(array $taken): int
    {
        $next = 1;
        while (in_array($next, $taken, true)) {
            $next++;
        }

        return $next;
    }

    private function ensureUnitNameIsFree(string $kind, string $name, ?int $ignoreId = null): void
    {
        $number = self::trailingNumber($name);
        $prefix = self::unitNumberPrefix($kind);

        if ($number !== null) {
            $taken = $this->takenUnitNumbers($kind, $ignoreId);
            if (in_array($number, $taken, true)) {
                throw ValidationException::withMessages([
                    'unit_number' => "{$prefix} {$number} is already taken. The next free number is {$prefix} {$this->nextUnitNumber($taken)}.",
                ]);
            }

            return;
        }

        $duplicate = AccommodationUnit::query()
            ->where('unit_type', $kind)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereRaw('LOWER(TRIM(unit_number)) = ?', [mb_strtolower(trim($name))])
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['unit_number' => "{$name} is already taken."]);
        }
    }

    private function unitValidationMessages(): array
    {
        $limit = ini_get('upload_max_filesize') ?: '256M';

        return [
            'tour_video.uploaded' => "The tour video is larger than the server allows ({$limit}). Compress it to an MP4 under that size, then save again.",
            'tour_video.mimes' => 'The tour video must be an MP4, WebM, or MOV file.',
            'tour_video.max' => 'The tour video must be 200 MB or smaller.',
        ];
    }
}
