<?php

namespace App\Http\Controllers;

use App\Models\AmenityItem;
use App\Models\AuditLog;
use App\Models\ChatbotIntent;
use App\Models\GalleryPhoto;
use App\Models\PaymentMethod;
use App\Models\ResortActivity;
use App\Models\SystemSetting;
use App\Models\UnitVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebFileMaintenanceController extends Controller
{
    public function index()
    {
        return view('maintenance.index', $this->counts() + [
            'section' => 'overview',
        ]);
    }

    public function section(string $section)
    {
        if ($section === 'gateway') {
            return redirect()->route('maintenance.section', 'payments');
        }
        if ($section === 'activities') {
            return redirect()->route('activities.manage');
        }

        $recordTabs = [
            'resort'    => 'general',
            'rules'     => 'booking',
            'assistant' => 'chatbot',
            'accounts'  => 'users',
        ];

        if (isset($recordTabs[$section])) {
            return view('maintenance.index', $this->counts() + app(WebControllers::class)->settingsViewData(request()) + [
                'section' => $section,
                'onlyTab' => $recordTabs[$section],
                'rows'    => collect(),
            ]);
        }

        if ($section === 'exclusive') {
            $copy = \App\Models\Booking::exclusiveCopy();

            return view('maintenance.index', $this->counts() + [
                'section' => 'exclusive',
                'rows' => collect(),
                'exclusiveTitle' => $copy['title'],
                'exclusiveDescription' => $copy['description'],
                'exclusiveInformation' => $copy['information'],
                'exclusiveRate' => \App\Models\SystemSetting::get('exclusive_nightly_rate', ''),
                'exclusiveOccasions' => \App\Models\SystemSetting::get('exclusive_occasions', implode("\n", \App\Models\Booking::EXCLUSIVE_OCCASIONS)),
                'exclusiveComputed' => app(\App\Services\BookingService::class)->exclusiveNightlyRate(),
            ]);
        }

        abort_unless($this->isSection($section), 404);

        return view('maintenance.index', $this->counts() + [
            'section' => $section,
            'rows'    => $this->rows($section),
        ]);
    }

    public function store(Request $request, string $section)
    {
        abort_unless($this->isSection($section), 404);
        $data = $this->validated($request, $section);
        $model = $this->model($section)::create($data + $this->uploads($request, $section));
        $this->syncGcashAccount($section, $model);
        AuditLog::log('file_maintenance_created', $model, null, ['section' => $section]);

        $message = $this->label($section).' added.';
        if ($section === 'payments' && $model->is_active && ! $model->offeredToGuests()) {
            $message .= ' Enter the account number so guests can choose it.';
        }

        return redirect()
            ->route('maintenance.section', $section)
            ->with('success', $message);
    }

    public function update(Request $request, string $section, int $id)
    {
        abort_unless($this->isSection($section), 404);
        $row = $this->model($section)::findOrFail($id);
        $data = $this->validated($request, $section, $row);
        $uploads = $this->uploads($request, $section, $row);
        $row->update($data + $uploads);
        $this->syncGcashAccount($section, $row);
        AuditLog::log('file_maintenance_updated', $row, null, ['section' => $section]);

        return redirect()
            ->route('maintenance.section', $section)
            ->with('success', $this->label($section).' updated.');
    }

    public function destroy(string $section, int $id)
    {
        abort_unless($this->isSection($section), 404);
        $row = $this->model($section)::findOrFail($id);
        if (($row->is_system ?? false) === true) {
            return back()->with('error', 'Built-in records stay in the system.');
        }
        if ($section === 'activities' && $row->image_path) {
            Storage::disk('public')->delete($row->image_path);
        }
        AuditLog::log('file_maintenance_removed', $row, ['section' => $section], null);
        $row->delete();

        return redirect()
            ->route('maintenance.section', $section)
            ->with('success', $this->label($section).' removed.');
    }

    public function installPayments(Request $request)
    {
        $request->validate([
            'codes'   => 'nullable|array|max:100',
            'codes.*' => 'string|max:40',
            'group'   => 'nullable|string|max:40',
        ]);
        $known = collect(PaymentMethod::presets())->keyBy('code');
        $codes = collect($request->input('codes', []));
        if ($request->filled('group')) {
            $group = (string) $request->input('group');
            $codes = $known->filter(fn ($preset) => $preset['group'] === $group)->keys();
        }

        $added = [];
        foreach ($codes as $code) {
            $code = (string) $code;
            $preset = $known->get($code);
            if (! $preset || PaymentMethod::query()->where('code', $code)->exists()) {
                continue;
            }
            $added[] = PaymentMethod::create([
                'code' => $preset['code'],
                'name' => $preset['name'],
                'kind' => $preset['kind'],
                'instructions' => $preset['instructions'],
                'icon' => $preset['icon'],
                'sort_order' => $preset['sort_order'],
                'is_system' => false,
                'is_active' => true,
            ]);
        }

        if ($added === []) {
            return redirect()
                ->route('maintenance.section', 'payments')
                ->with('success', 'Those payments are already in the list.');
        }

        $names = collect($added)->pluck('name')->join(', ');
        $needsAccount = collect($added)->contains(fn (PaymentMethod $row) => ! in_array($row->kind, ['cash', 'card'], true));
        $message = $names.' added.';
        if ($needsAccount) {
            $message .= ' Enter the account number on each one. It appears on every booking form once that number is saved.';
        }

        return redirect()
            ->route('maintenance.section', 'payments')
            ->with('success', $message);
    }

    private function isSection(string $section): bool
    {
        return in_array($section, ['variants', 'payments', 'amenities', 'replies'], true);
    }

    private function label(string $section): string
    {
        return match ($section) {
            'activities' => 'Activity',
            'variants'   => 'Variant',
            'payments'   => 'Payment method',
            'amenities'  => 'Amenity',
            'replies'    => 'Chat reply',
            default      => 'Record',
        };
    }

    private function model(string $section): string
    {
        return match ($section) {
            'activities' => ResortActivity::class,
            'variants'   => UnitVariant::class,
            'payments'   => PaymentMethod::class,
            'amenities'  => AmenityItem::class,
            'replies'    => ChatbotIntent::class,
        };
    }

    private function rows(string $section)
    {
        $query = $this->model($section)::query();
        if ($section === 'replies') {
            return $query->orderBy('category')->orderBy('keyword')->get();
        }

        return $query->orderBy('sort_order')->orderBy('id')->get();
    }

    private function counts(): array
    {
        return [
            'activityCount' => ResortActivity::query()->count(),
            'variantCount'  => UnitVariant::query()->count(),
            'paymentCount'  => PaymentMethod::query()->count(),
            'amenityCount'  => AmenityItem::query()->count(),
            'replyCount'    => ChatbotIntent::query()->count(),
            'galleryCount'  => GalleryPhoto::query()->count(),
            'userCount'     => User::query()->count(),
        ];
    }

    private function validated(Request $request, string $section, $row = null): array
    {
        if ($section === 'activities') {
            $data = $request->validate([
                'title'       => 'required|string|max:80',
                'description' => 'nullable|string|max:500',
                'schedule'    => 'nullable|string|max:120',
                'price'       => 'nullable|numeric|min:0',
                'icon'        => 'nullable|string|max:40',
                'sort_order'  => 'nullable|integer|min:0',
                'is_active'   => 'nullable|boolean',
                'is_bookable' => 'nullable|boolean',
                'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            ]);
            unset($data['image']);
            $data['icon'] = ($data['icon'] ?? null) ?: 'bi-water';
            $data['is_active'] = $request->boolean('is_active', true);
            $data['is_bookable'] = $request->boolean('is_bookable');
            $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

            return $data;
        }

        if ($section === 'variants') {
            $slugRule = Rule::unique('unit_variants', 'slug');
            if ($row) {
                $slugRule->ignore($row->id);
            }
            $data = $request->validate([
                'label'      => 'required|string|max:80',
                'slug'       => ['nullable', 'string', 'max:40', 'alpha_dash', $slugRule],
                'applies_to' => 'required|in:room,cottage,both',
                'price_per_night' => 'nullable|numeric|min:0',
                'is_deluxe'  => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'is_active'  => 'nullable|boolean',
            ]);
            if ($row?->is_system || ($row && ! $request->filled('slug'))) {
                $data['slug'] = $row->slug;
            } else {
                $base = Str::slug((string) (($data['slug'] ?? null) ?: $data['label'])) ?: 'variant';
                $slug = $base;
                $n = 2;
                while (UnitVariant::query()->where('slug', $slug)->when($row, fn ($q) => $q->whereKeyNot($row->id))->exists()) {
                    $slug = $base.'-'.$n;
                    $n++;
                }
                $data['slug'] = $slug;
            }
            $data['price_per_night'] = ($data['price_per_night'] ?? '') === '' ? null : $data['price_per_night'];
            $data['is_deluxe'] = $request->exists('is_deluxe')
                ? $request->boolean('is_deluxe')
                : (bool) ($row->is_deluxe ?? false);
            $data['is_active'] = $request->boolean('is_active', true);
            $data['sort_order'] = $request->exists('sort_order')
                ? (int) $request->input('sort_order')
                : (int) ($row->sort_order ?? ((int) UnitVariant::query()->max('sort_order') + 1));

            return $data;
        }

        if ($section === 'payments') {
            $codeRule = Rule::unique('payment_methods', 'code');
            if ($row) {
                $codeRule->ignore($row->id);
            }
            $data = $request->validate([
                'name'            => 'required|string|max:80',
                'code'            => ['nullable', 'string', 'max:40', 'alpha_dash', $codeRule],
                'kind'            => 'required|in:cash,ewallet,bank,card,online',
                'instructions'    => 'nullable|string|max:400',
                'account_name'    => 'nullable|string|max:80',
                'account_number'  => 'nullable|string|max:40',
                'icon'            => 'nullable|string|max:40',
                'sort_order'      => 'nullable|integer|min:0',
                'is_active'       => 'nullable|boolean',
            ]);
            if ($row?->is_system) {
                $data['code'] = $row->code;
                $data['kind'] = $row->kind;
            } elseif ($row && ! $request->filled('code')) {
                $data['code'] = $row->code;
            } else {
                $base = Str::slug(($data['code'] ?? null) ?: $data['name'], '_') ?: 'method';
                $code = $base;
                $n = 2;
                while (PaymentMethod::query()->where('code', $code)->when($row, fn ($q) => $q->whereKeyNot($row->id))->exists()) {
                    $code = $base.'_'.$n;
                    $n++;
                }
                $data['code'] = $code;
            }
            $data['is_active'] = $request->boolean('is_active', true);
            $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
            $data['icon'] = ($data['icon'] ?? null) ?: match ($data['kind']) {
                'cash' => 'bi-cash-stack',
                'card' => 'bi-credit-card-fill',
                'bank' => 'bi-bank',
                'online' => 'bi-globe2',
                default => 'bi-phone-fill',
            };

            return $data;
        }

        if ($section === 'replies') {
            $data = $request->validate([
                'keyword'   => 'required|string|max:255',
                'category'  => 'required|in:'.implode(',', ChatbotIntent::CATEGORIES),
                'response'  => 'required|string|max:2000',
                'is_active' => 'nullable|boolean',
            ]);
            $data['keyword'] = trim($data['keyword']);
            $data['response'] = trim($data['response']);
            $data['is_active'] = $request->boolean('is_active', true);

            return $data;
        }

        $data = $request->validate([
            'name'       => 'required|string|max:80',
            'icon'       => 'nullable|string|max:40',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['icon'] = $request->filled('icon')
            ? $data['icon']
            : ($row?->icon ?? 'bi-check2-square');
        $data['sort_order'] = $request->exists('sort_order')
            ? (int) $request->input('sort_order')
            : (int) ($row?->sort_order ?? ((int) AmenityItem::query()->max('sort_order') + 1));

        return $data;
    }

    private function uploads(Request $request, string $section, $row = null): array
    {
        if ($section !== 'activities' || ! $request->hasFile('image')) {
            return [];
        }
        if ($row?->image_path) {
            Storage::disk('public')->delete($row->image_path);
        }

        return ['image_path' => $request->file('image')->store('activities', 'public')];
    }

    private function syncGcashAccount(string $section, $row): void
    {
        if ($section !== 'payments' || ($row->code ?? '') !== 'gcash') {
            return;
        }

        // Empty fields keep the saved GCash details; turn the method off to stop offering it.
        if (filled($row->account_name ?? null)) {
            SystemSetting::set('gcash_name', (string) $row->account_name);
        }
        if (filled($row->account_number ?? null)) {
            SystemSetting::set('gcash_number', (string) $row->account_number);
        }
    }
}
