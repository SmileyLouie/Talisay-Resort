<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ResortActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebActivityController extends Controller
{
    public function index()
    {
        $activities = ResortActivity::query()->orderBy('sort_order')->orderBy('id')->get();

        return view('activities.manage', [
            'activities' => $activities,
            'showing' => $activities->where('is_active', true)->count(),
            'bookable' => $activities->where('is_bookable', true)->where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $activity = ResortActivity::create($this->validated($request) + $this->upload($request));
        AuditLog::log('activity_created', $activity);

        return redirect()->route('activities.manage')->with('success', $activity->title.' added.');
    }

    public function update(Request $request, ResortActivity $activity)
    {
        $activity->update($this->validated($request, $activity) + $this->upload($request, $activity));
        AuditLog::log('activity_updated', $activity);

        return redirect()->route('activities.manage')->with('success', $activity->title.' updated.');
    }

    public function destroy(ResortActivity $activity)
    {
        if ($activity->image_path) {
            Storage::disk('public')->delete($activity->image_path);
        }
        AuditLog::log('activity_removed', $activity);
        $activity->delete();

        return redirect()->route('activities.manage')->with('success', 'Activity removed.');
    }

    private function validated(Request $request, ?ResortActivity $activity = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:80',
            'description' => 'nullable|string|max:2000',
            'schedule' => 'nullable|string|max:120',
            'duration' => 'nullable|string|max:80',
            'meeting_point' => 'nullable|string|max:160',
            'included' => 'nullable|string|max:500',
            'what_to_bring' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0',
            'max_guests' => 'nullable|integer|min:1|max:500',
            'icon' => 'nullable|string|max:40',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);
        unset($data['image']);
        $data['icon'] = ($data['icon'] ?? '') !== '' ? $data['icon'] : 'bi-water';
        $data['price'] = $data['price'] ?? null;
        $data['max_guests'] = isset($data['max_guests']) ? (int) $data['max_guests'] : null;
        $data['sort_order'] = (int) ($data['sort_order'] ?? ($activity->sort_order ?? 0));
        $data['is_active'] = $request->boolean('is_active');
        $data['is_bookable'] = $request->boolean('is_bookable');

        return $data;
    }

    private function upload(Request $request, ?ResortActivity $activity = null): array
    {
        if (! $request->hasFile('image')) {
            return [];
        }
        if ($activity?->image_path) {
            Storage::disk('public')->delete($activity->image_path);
        }

        return ['image_path' => $request->file('image')->store('activities', 'public')];
    }
}
