{{-- Extends the primary layout template designed for dashboard panels --}}
@extends('layouts.app')

{{-- Sets the HTML document title for the virtual 360 tour page --}}
@section('title', 'Virtual Tour - Talisay Smart Tourism')

{{-- Push Pannellum.js CDN CSS styling dependencies --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
<style>
    /* Styling to support full width and height for the panorama viewer container */
    #panoramaViewer {
        width: 100%;
        height: 100%;
        background-color: #0c4a6e;
    }
</style>
@endpush

{{-- Defines the main content area section of the template --}}
@section('content')

{{-- Header layout block containing page title and subtitle details --}}
<div class="mb-6">
    {{-- Principal title heading for virtual tour --}}
    <h1 class="text-2xl font-bold text-gray-800">Virtual 360-Degree Tour</h1>
    {{-- Subtitle info label --}}
    <p class="text-gray-500 text-sm">Explore Talisay Beach Resort in 360-degree panoramic viewpoints</p>
</div>

{{-- Main viewport card container --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-4">
    {{-- Set aspect ratio container to standard 16x9 matching viewer sizes --}}
    <div class="ratio ratio-16x9" style="min-height:500px;">
        {{-- Div wrapper targeted by Pannellum Javascript library --}}
        <div id="panoramaViewer">
            {{-- Fallback container visible if no assets are uploaded yet --}}
            @if($assets->isEmpty())
            <div class="text-center p-8 flex flex-col items-center justify-center text-white h-full">
                <i class="bi bi-geo-alt-fill text-5xl mb-3"></i>
                <h3>Talisay Beach Resort Virtual Tour</h3>
                <p class="text-white/70">Upload panoramic images in Tour Assets management to enable the 360 viewer.</p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Render scene selection list if scenes are active --}}
@if($assets->count() > 0)
<div class="mb-2"><h4 class="font-bold text-gray-800 text-lg">Select a Scene viewpoint</h4></div>
<div class="row g-3">
    {{-- Loop through each active scene --}}
    @foreach($assets as $asset)
    <div class="col-md-4 col-lg-3">
        {{-- Trigger button navigation to switch viewpoints --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden cursor-pointer hover:border-sky-500 border transition-all" onclick="loadPanoramaScene('scene_{{ $asset->id }}')">
            {{-- Image previews placeholder with gradient backing --}}
            <div class="bg-gradient-to-br from-sky-400 to-teal-400 h-24 flex items-center justify-center">
                <i class="bi bi-geo-alt-fill text-white text-3xl"></i>
            </div>
            {{-- Detail titles within small info card --}}
            <div class="p-3">
                <h6 class="font-bold mb-1 text-sm">{{ $asset->title }}</h6>
                <p class="text-xs text-gray-500 text-truncate">{{ $asset->description }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection

{{-- Push Pannellum.js CDN JS library --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
<script>
// Check if active assets list is present
@if($assets->count() > 0)

// Build database-driven multi-scene configuration array for Pannellum
const tourScenes = {};

// Iterate and populate scenes into local javascript map structure
@foreach($assets as $index => $asset)
tourScenes['scene_{{ $asset->id }}'] = {
    // Set title label
    title: "{{ $asset->title }}",
    // Set image type configuration
    type: "equirectangular",
    // Convert seeded image paths or user uploaded storage URLs
    panorama: "{{ $asset->panorama_path ? (Str::startsWith($asset->panorama_path, 'http') ? $asset->panorama_path : Storage::url($asset->panorama_path)) : '' }}",
    // Enable auto rotating features
    autoLoad: true,
    // Map hotspots
    hotspots: [
        // Loop hotspots definitions
        @if($asset->hotspots)
        @foreach($asset->hotspots as $hotspot)
        {
            pitch: {{ $hotspot['pitch'] ?? 0 }},
            yaw: {{ $hotspot['yaw'] ?? 0 }},
            type: "{{ ($hotspot['type'] ?? 'info') === 'scene' ? 'scene' : 'info' }}",
            text: "{{ $hotspot['text'] ?? '' }}",
            // Map scene target transitions if hotspot matches scene type navigation
            @if(($hotspot['type'] ?? 'info') === 'scene')
            sceneId: "scene_{{ $hotspot['sceneId'] ?? '1' }}",
            @endif
        },
        @endforeach
        @endif
    ]
};
@endforeach

// Instantiate Pannellum multiscene tour viewer
const viewer = pannellum.viewer('panoramaViewer', {
    // Configure default parameters
    default: {
        // Set first scene ID as starting viewpoint
        firstScene: "scene_{{ $assets->first()->id }}",
        // Enable author watermarks
        author: "Talisay Beach Resort",
        // Enable scene fade transitions effects
        sceneFadeDuration: 1000
    },
    // Set matching parsed scenes map
    scenes: tourScenes
});

// Javascript method to trigger manual scene changes from button selection grids
function loadPanoramaScene(sceneId) {
    // Change active viewer scene viewpoint
    viewer.loadScene(sceneId);
}

@endif
</script>
@endpush