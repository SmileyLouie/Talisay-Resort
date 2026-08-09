<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TourAsset;
use Illuminate\Http\Request;

class TourAssetController extends Controller
{
    public function publicIndex()
    {
        $assets = TourAsset::active()->get();
        return response()->json($assets);
    }

    public function index()
    {
        return response()->json(TourAsset::orderBy('sort_order')->paginate(20));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'panorama' => 'required|file|max:51200',
            'type' => 'in:image,video',
            'hotspots' => 'nullable|json',
            'sort_order' => 'integer',
        ]);

        $path = $request->file('panorama')->store('tour_panoramas', 'public');

        $asset = TourAsset::create([
            'title' => $request->title,
            'description' => $request->description,
            'panorama_path' => $path,
            'type' => $request->type ?? 'image',
            'hotspots' => $request->hotspots ? json_decode($request->hotspots, true) : null,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return response()->json(['asset' => $asset, 'message' => 'Tour asset created.'], 201);
    }

    public function show(TourAsset $tourAsset)
    {
        return response()->json($tourAsset);
    }

    public function update(Request $request, TourAsset $tourAsset)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'panorama' => 'nullable|file|max:51200',
            'type' => 'in:image,video',
            'hotspots' => 'nullable|json',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $data = $request->except('panorama');

        if ($request->hasFile('panorama')) {
            \Storage::disk('public')->delete($tourAsset->panorama_path);
            $data['panorama_path'] = $request->file('panorama')->store('tour_panoramas', 'public');
        }

        if ($request->has('hotspots')) {
            $data['hotspots'] = json_decode($request->hotspots, true);
        }

        $tourAsset->update($data);

        return response()->json(['asset' => $tourAsset->fresh(), 'message' => 'Tour asset updated.']);
    }

    public function destroy(TourAsset $tourAsset)
    {
        \Storage::disk('public')->delete($tourAsset->panorama_path);
        $tourAsset->delete();
        return response()->json(['message' => 'Tour asset deleted.']);
    }
}
