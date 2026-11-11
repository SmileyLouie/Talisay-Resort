<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebSettingsController extends WebControllers
{
    public function index(Request $request) { return $this->settingsIndex($request); }
    public function update(Request $request) { return $this->settingsUpdate($request); }
    public function updateChatbot(Request $request) { return $this->settingsUpdateChatbot($request); }
    public function testAiConnection(Request $request) { return $this->settingsTestAiConnection($request); }

    public function storeGallery(Request $request)
    {
        $data = $request->validate([
            'photos'   => 'required|array|min:1|max:20',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
            'title'    => 'nullable|string|max:80',
            'caption'  => 'nullable|string|max:160',
        ], [
            'photos.required' => 'Choose at least one photo of a view or attraction.',
            'photos.*.image'  => 'Each gallery file must be a photo.',
            'photos.*.mimes'  => 'Photos must be JPG, PNG, or WebP.',
            'photos.*.max'    => 'Each photo must be 8 MB or smaller.',
        ]);

        $files = $request->file('photos', []);
        $single = count($files) === 1;
        $title = $single ? $this->galleryText($data['title'] ?? null) : null;
        $caption = $single ? $this->galleryText($data['caption'] ?? null) : null;
        $order = (int) GalleryPhoto::query()->max('sort_order');

        foreach ($files as $file) {
            $order++;
            $photo = GalleryPhoto::create([
                'title'       => $title,
                'caption'     => $caption,
                'image_path'  => $file->store('gallery', 'public'),
                'sort_order'  => $order,
            ]);
            AuditLog::log('gallery_photo_uploaded', $photo, null, ['title' => $photo->title]);
        }

        $count = count($files);

        return redirect()
            ->route('maintenance.section', 'resort')
            ->with('success', $count === 1 ? 'Gallery photo added.' : $count.' gallery photos added.');
    }

    public function updateGallery(Request $request, GalleryPhoto $photo)
    {
        $data = $request->validate([
            'title'   => 'nullable|string|max:80',
            'caption' => 'nullable|string|max:160',
        ]);

        $photo->update([
            'title'   => $this->galleryText($data['title'] ?? null),
            'caption' => $this->galleryText($data['caption'] ?? null),
        ]);

        AuditLog::log('gallery_photo_updated', $photo, null, [
            'title'   => $photo->title,
            'caption' => $photo->caption,
        ]);

        return redirect()
            ->route('maintenance.section', 'resort')
            ->with('success', 'Gallery photo updated.');
    }

    public function destroyGallery(GalleryPhoto $photo)
    {
        if ($photo->image_path) {
            Storage::disk('public')->delete($photo->image_path);
        }

        AuditLog::log('gallery_photo_removed', $photo, ['title' => $photo->title], null);
        $photo->delete();

        return redirect()
            ->route('maintenance.section', 'resort')
            ->with('success', 'Gallery photo removed.');
    }

    private function galleryText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
