<?php

namespace App\Http\Controllers;

use App\Models\AccommodationUnit;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebAccommodationController extends WebControllers
{
    // ─── Admin/Staff: List all units ──────────────────────────────────
    public function index(Request $request)
    {
        $filter = $request->filter ?? 'all';
        $query = AccommodationUnit::orderBy('unit_type')->orderBy('sort_order');

        if ($filter === 'rooms') $query->rooms();
        elseif ($filter === 'cottages') $query->cottages();
        elseif ($filter === 'normal') $query->normal();
        elseif ($filter === 'premium') $query->premium();

        $units = $query->get();

        $stats = [
            'total'     => AccommodationUnit::count(),
            'rooms'     => AccommodationUnit::rooms()->count(),
            'cottages'  => AccommodationUnit::cottages()->count(),
            'available' => AccommodationUnit::available()->count(),
        ];

        return view('accommodations.index', compact('units', 'filter', 'stats'));
    }

    // ─── Admin: Store new unit ────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_number'       => 'required|string|max:50',
            'unit_type'         => 'required|in:room,cottage',
            'variant'           => 'required|in:normal,premium',
            'floor_area_sqm'    => 'nullable|numeric|min:1',
            'bed_configuration' => 'nullable|string|max:100',
            'max_occupancy'     => 'required|integer|min:1',
            'amenities'         => 'nullable|string',
            'description'       => 'nullable|string',
            'price_per_night'   => 'required|numeric|min:0',
            'tour_video'        => 'nullable|file|mimes:mp4,webm,ogv|max:204800', // 200MB
            'images.*'          => 'nullable|image|max:5120',
            'sort_order'        => 'nullable|integer',
            'is_available'      => 'nullable|boolean',
        ]);

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
            'floor_area_sqm'    => $data['floor_area_sqm'] ?? null,
            'bed_configuration' => $data['bed_configuration'] ?? null,
            'max_occupancy'     => $data['max_occupancy'],
            'amenities'         => $amenitiesArray,
            'description'       => $data['description'] ?? null,
            'images'            => $imagePaths,
            'tour_video_path'   => $tourVideoPath,
            'price_per_night'   => $data['price_per_night'],
            'is_available'      => $request->boolean('is_available', true),
            'sort_order'        => $data['sort_order'] ?? 0,
        ]);

        AuditLog::log('accommodation_unit_created', $unit, null, $unit->toArray());

        return redirect()->route('accommodations.index')
            ->with('success', "Unit {$unit->unit_number} created successfully!");
    }

    // ─── Admin: Update unit ───────────────────────────────────────────
    public function update(Request $request, AccommodationUnit $accommodation)
    {
        $data = $request->validate([
            'unit_number'       => 'required|string|max:50',
            'unit_type'         => 'required|in:room,cottage',
            'variant'           => 'required|in:normal,premium',
            'floor_area_sqm'    => 'nullable|numeric|min:1',
            'bed_configuration' => 'nullable|string|max:100',
            'max_occupancy'     => 'required|integer|min:1',
            'amenities'         => 'nullable|string',
            'description'       => 'nullable|string',
            'price_per_night'   => 'required|numeric|min:0',
            'tour_video'        => 'nullable|file|mimes:mp4,webm,ogv|max:204800',
            'images.*'          => 'nullable|image|max:5120',
            'sort_order'        => 'nullable|integer',
            'is_available'      => 'nullable|boolean',
        ]);

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
            'floor_area_sqm'    => $data['floor_area_sqm'] ?? null,
            'bed_configuration' => $data['bed_configuration'] ?? null,
            'max_occupancy'     => $data['max_occupancy'],
            'amenities'         => $amenitiesArray,
            'description'       => $data['description'] ?? null,
            'images'            => $imagePaths,
            'tour_video_path'   => $tourVideoPath,
            'price_per_night'   => $data['price_per_night'],
            'is_available'      => $request->boolean('is_available', true),
            'sort_order'        => $data['sort_order'] ?? 0,
        ]);

        AuditLog::log('accommodation_unit_updated', $accommodation, $old, $accommodation->toArray());

        return redirect()->route('accommodations.index')
            ->with('success', "Unit {$accommodation->unit_number} updated successfully!");
    }

    // ─── Admin: Delete unit ───────────────────────────────────────────
    public function destroy(AccommodationUnit $accommodation)
    {
        $name = $accommodation->unit_number;
        if ($accommodation->tour_video_path) {
            Storage::disk('public')->delete($accommodation->tour_video_path);
        }
        $accommodation->delete();

        AuditLog::log('accommodation_unit_deleted', $accommodation);

        return redirect()->route('accommodations.index')
            ->with('success', "Unit {$name} deleted.");
    }

    // ─── Admin: Toggle availability ───────────────────────────────────
    public function toggleAvailability(AccommodationUnit $accommodation)
    {
        $accommodation->update(['is_available' => !$accommodation->is_available]);

        $status = $accommodation->is_available ? 'available' : 'unavailable';
        return redirect()->route('accommodations.index')
            ->with('success', "{$accommodation->unit_number} is now {$status}.");
    }

    // ─── Tourist: Browse all accommodations ───────────────────────────
    public function guestIndex(Request $request)
    {
        $filter = $request->filter ?? 'all';
        $query = AccommodationUnit::available()->orderBy('unit_type')->orderBy('sort_order');

        if ($filter === 'rooms') $query->rooms();
        elseif ($filter === 'cottages') $query->cottages();
        elseif ($filter === 'normal') $query->normal();
        elseif ($filter === 'premium') $query->premium();

        $units = $query->get();

        return view('tourist.accommodations', compact('units', 'filter'));
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
}
