<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Virtual 360° Tour & Interactive Resort Map – Talisay Beach Resort</title>

    {{-- Universal Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    {{-- Tailwind CSS & Bootstrap Icons --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Pannellum 360 Panoramic Viewer CDN --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

    {{-- Leaflet Interactive Map CDN --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        :root {
            --brand-ocean: #0284c7;
            --brand-dark: #041226;
            --brand-accent: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #041226;
            color: #f8fafc;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Fullscreen Viewport Containers */
        #panoramaContainer {
            width: 100%;
            height: 100%;
            background-color: #030d1a;
            position: relative;
        }

        #panoramaViewer {
            width: 100%;
            height: 100%;
        }

        #mapContainer {
            width: 100%;
            height: 100%;
            position: relative;
            background: #091a30;
        }

        #leafletMap {
            width: 100%;
            height: 100%;
            z-index: 10;
        }

        /* Custom Mode Transitions */
        .viewport-wrapper {
            position: relative;
            width: 100%;
            height: calc(100vh - 185px);
            min-height: 520px;
            overflow: hidden;
            border-radius: 16px;
            background: #020b17;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.08);
        }

        @media (max-width: 768px) {
            .viewport-wrapper {
                height: calc(100vh - 240px);
                min-height: 420px;
                border-radius: 0;
            }
        }

        /* Split Mode Layout */
        .split-active #panoramaCol {
            width: 55%;
            height: 100%;
            float: left;
            position: relative;
            border-right: 2px solid rgba(255, 255, 255, 0.12);
        }
        .split-active #mapCol {
            width: 45%;
            height: 100%;
            float: left;
            position: relative;
        }
        @media (max-width: 900px) {
            .split-active #panoramaCol {
                width: 100%;
                height: 50%;
                border-right: none;
                border-bottom: 2px solid rgba(255, 255, 255, 0.12);
            }
            .split-active #mapCol {
                width: 100%;
                height: 50%;
            }
        }

        /* Map Radar Marker Animation */
        .radar-pin {
            position: relative;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #0284c7;
            color: #fff;
            font-size: 16px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.6);
            border: 2px solid #ffffff;
            cursor: pointer;
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .radar-pin:hover {
            transform: scale(1.18);
        }
        .radar-pin.active {
            background: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.4), 0 6px 20px rgba(245, 158, 11, 0.7);
        }
        .radar-pin.active::after {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 2px solid #f59e0b;
            animation: pulseRadar 1.8s infinite ease-out;
        }
        @keyframes pulseRadar {
            0% { transform: scale(0.7); opacity: 1; }
            100% { transform: scale(1.8); opacity: 0; }
        }

        /* Floating Mini-Map Radar */
        .minimap-radar {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 240px;
            height: 180px;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.6), 0 0 0 2px rgba(255, 255, 255, 0.25);
            background: #07192f;
            z-index: 30;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .minimap-radar.minimized {
            width: 48px;
            height: 48px;
            border-radius: 50%;
        }
        .minimap-radar.minimized #miniMapLeaflet {
            display: none;
        }

        /* Pannellum Hotspot Custom Styling */
        .pnlm-hotspot-base {
            background-color: rgba(14, 165, 233, 0.85);
            border: 2px solid #ffffff;
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(14, 165, 233, 0.8);
            transition: all 0.2s ease;
        }
        .pnlm-hotspot-base:hover {
            background-color: #f59e0b;
            transform: scale(1.2);
            box-shadow: 0 0 18px rgba(245, 158, 11, 0.9);
        }

        /* Glassmorphism Controls Overlay */
        .glass-panel {
            background: rgba(4, 18, 38, 0.78);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        /* Custom Scrollbar for Scene Rail */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Leaflet Dark Customizations */
        .leaflet-popup-content-wrapper {
            background: #0a2540;
            color: #fff;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.5);
        }
        .leaflet-popup-tip {
            background: #0a2540;
        }
    </style>
</head>
<body class="bg-slate-950 flex flex-col min-h-screen">

    {{-- Top Navigation Header --}}
    <header class="glass-panel sticky top-0 z-50 px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3 border-b border-white/10 shadow-lg">
        {{-- Brand & Title --}}
        <div class="flex items-center gap-3">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-white no-underline group">
                <img src="{{ asset('logo.png') }}" alt="Talisay Beach Resort" class="w-9 h-9 rounded-xl object-contain bg-white/10 p-1 border border-white/20 shadow group-hover:scale-105 transition">
                <div>
                    <div class="font-bold text-sm sm:text-base leading-tight tracking-tight font-['Playfair_Display']">Talisay Beach Resort</div>
                    <div class="text-[11px] text-sky-300 font-semibold tracking-wider flex items-center gap-1.5 uppercase">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Virtual 360° Tour &amp; Resort Map
                    </div>
                </div>
            </a>
            
            {{-- Geographic Location Badge --}}
            <div class="hidden lg:flex items-center gap-1.5 bg-white/5 border border-white/10 px-3 py-1 rounded-full text-xs text-slate-300">
                <i class="bi bi-geo-alt-fill text-amber-400 text-xs"></i>
                <span>Brgy. Maslug, Baybay City, Leyte</span>
            </div>
        </div>

        {{-- Center: Mode Switcher Tabs --}}
        <div class="flex items-center bg-slate-900/90 p-1 rounded-xl border border-white/10 shadow-inner order-3 md:order-2 w-full md:w-auto justify-center">
            <button id="tabBtnPano" onclick="switchViewMode('pano')" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 text-white bg-sky-600 shadow">
                <i class="bi bi-camera-video-fill"></i>
                <span>360° Panorama</span>
            </button>
            <button id="tabBtnSplit" onclick="switchViewMode('split')" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1.5">
                <i class="bi bi-layout-split"></i>
                <span class="hidden sm:inline">Split</span> View
            </button>
            <button id="tabBtnMap" onclick="switchViewMode('map')" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1.5">
                <i class="bi bi-map-fill"></i>
                <span>Resort Map</span>
            </button>
            <button id="tabBtnVideo" onclick="switchViewMode('video')" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1.5">
                <i class="bi bi-play-circle-fill"></i>
                <span class="hidden sm:inline">Video</span> Tour
            </button>
        </div>

        {{-- Right Actions --}}
        <div class="flex items-center gap-2 order-2 md:order-3">
            {{-- Google Maps External Directions --}}
            <a href="https://www.google.com/maps/dir/?api=1&destination=10.5807,124.7656" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold px-3 py-1.5 rounded-xl border border-white/15 transition no-underline shadow-sm" title="Get Driving Directions in Google Maps">
                <i class="bi bi-signpost-2-fill text-amber-400"></i>
                <span>Google Maps</span>
            </a>

            {{-- Booking / Return Navigation --}}
            @if(auth()->check())
                <a href="{{ auth()->user()->isTourist() ? route('tourist.dashboard') : (auth()->user()->isAdmin() ? route('admin.dashboard') : route('staff.dashboard')) }}" class="bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-400 hover:to-sky-500 text-white text-xs font-bold px-3.5 py-1.5 rounded-xl shadow transition no-underline flex items-center gap-1.5">
                    <i class="bi bi-grid-fill"></i>
                    <span>Dashboard</span>
                </a>
            @else
                <a href="{{ url('/') }}" class="bg-white/10 hover:bg-white/20 text-white text-xs font-semibold px-3 py-1.5 rounded-xl border border-white/15 transition no-underline flex items-center gap-1.5">
                    <i class="bi bi-arrow-left"></i>
                    <span>Resort Home</span>
                </a>
                <a href="{{ route('login') }}" class="bg-sky-500 hover:bg-sky-400 text-white text-xs font-bold px-3.5 py-1.5 rounded-xl shadow transition no-underline flex items-center gap-1.5">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Book / Login</span>
                </a>
            @endif
        </div>
    </header>

    {{-- Main Tour Experience Viewport --}}
    <main class="flex-1 p-2 sm:p-4 lg:p-6 flex flex-col max-w-7xl w-full mx-auto">

        {{-- Interactive Viewport Container --}}
        <div class="viewport-wrapper relative" id="tourViewport">

            {{-- Column 1: 360 Panorama Viewer --}}
            <div id="panoramaCol" class="w-full h-full relative">
                <div id="panoramaContainer">
                    <div id="panoramaViewer"></div>

                    {{-- Empty State Fallback --}}
                    @if($assets->isEmpty())
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-6 text-white bg-slate-900 z-10">
                        <i class="bi bi-camera-video text-6xl text-sky-400 mb-4 animate-bounce"></i>
                        <h3 class="text-xl font-bold mb-2">No Tour Panoramas Active</h3>
                        <p class="text-slate-400 text-sm max-w-md">Activate panoramic scenes in Tour Assets management to enable the 360 viewer.</p>
                    </div>
                    @endif

                    {{-- Floating Scene Info Badge (Top Left) --}}
                    <div class="absolute top-4 left-4 z-20 pointer-events-none max-w-sm">
                        <div class="glass-panel p-3 sm:p-4 rounded-2xl shadow-xl pointer-events-auto border border-white/15 animate-fade">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="bg-sky-500/30 text-sky-300 border border-sky-400/40 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-ping"></span>
                                    Active Viewpoint
                                </span>
                                <span id="sceneBadgeCoordinates" class="text-[10px] text-slate-400 font-mono">10.5807°N, 124.7656°E</span>
                            </div>
                            <h2 id="sceneInfoTitle" class="text-white font-bold text-base sm:text-lg leading-tight mb-1">
                                {{ $assets->first()->title ?? 'Main Beach Entrance' }}
                            </h2>
                            <p id="sceneInfoDesc" class="text-slate-300 text-xs line-clamp-2 leading-relaxed mb-0">
                                {{ $assets->first()->description ?? 'Explore Talisay Beach Resort in 360-degree viewpoints.' }}
                            </p>
                        </div>
                    </div>

                    {{-- Floating On-Screen Viewer Controls (Bottom Left) --}}
                    <div class="absolute bottom-4 left-4 z-20 flex items-center gap-2">
                        <div class="glass-panel p-1.5 rounded-2xl flex items-center gap-1 shadow-2xl border border-white/15">
                            <button id="btnAutoRotate" onclick="toggleAutoRotate()" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-sky-500 text-white flex items-center justify-center transition" title="Toggle 360° Auto-Rotation">
                                <i id="iconAutoRotate" class="bi bi-play-fill text-lg"></i>
                            </button>
                            <button onclick="viewer.setPitch(0); viewer.setYaw(0);" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-sky-500 text-white flex items-center justify-center transition" title="Reset View Orientation">
                                <i class="bi bi-compass text-base"></i>
                            </button>
                            <button onclick="viewer.setHfov(viewer.getHfov() - 15);" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-sky-500 text-white flex items-center justify-center transition" title="Zoom In (+)">
                                <i class="bi bi-zoom-in text-base"></i>
                            </button>
                            <button onclick="viewer.setHfov(viewer.getHfov() + 15);" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-sky-500 text-white flex items-center justify-center transition" title="Zoom Out (-)">
                                <i class="bi bi-zoom-out text-base"></i>
                            </button>
                            <button onclick="togglePanoFullscreen()" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-sky-500 text-white flex items-center justify-center transition" title="Toggle Fullscreen">
                                <i class="bi bi-arrows-fullscreen text-sm"></i>
                            </button>
                        </div>

                        {{-- Direct View on Map shortcut --}}
                        <button onclick="switchViewMode('split')" class="glass-panel px-3 py-2 rounded-2xl text-xs font-bold text-sky-300 hover:text-white hover:bg-sky-600 transition flex items-center gap-1.5 border border-white/15 shadow-xl">
                            <i class="bi bi-map-fill"></i>
                            <span class="hidden sm:inline">Open Resort Map</span>
                        </button>
                    </div>

                    {{-- Floating Radar Mini-Map (Bottom Right) --}}
                    <div id="miniMapWidget" class="minimap-radar">
                        <div class="bg-slate-900/90 px-3 py-1.5 flex items-center justify-between text-[11px] text-white font-bold border-b border-white/10">
                            <span class="flex items-center gap-1 text-sky-300">
                                <i class="bi bi-broadcast text-xs text-emerald-400 animate-pulse"></i>
                                Radar Mini-Map
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button onclick="switchViewMode('map')" class="text-slate-400 hover:text-white transition" title="Expand Full Map">
                                    <i class="bi bi-arrows-angle-expand text-xs"></i>
                                </button>
                                <button onclick="toggleMiniMapCollapse()" class="text-slate-400 hover:text-white transition" title="Collapse / Expand">
                                    <i id="miniMapToggleIcon" class="bi bi-dash text-sm"></i>
                                </button>
                            </div>
                        </div>
                        <div id="miniMapLeaflet" style="width: 100%; height: calc(100% - 30px);"></div>
                    </div>
                </div>
            </div>

            {{-- Column 2: Leaflet Interactive Resort Map --}}
            <div id="mapCol" class="w-full h-full relative hidden">
                <div id="mapContainer">
                    <div id="leafletMap"></div>

                    {{-- Floating Map Layer Switcher & Filter (Top Right) --}}
                    <div class="absolute top-4 right-4 z-20 flex flex-col items-end gap-2">
                        <div class="glass-panel p-1.5 rounded-2xl flex items-center gap-1 shadow-2xl border border-white/15">
                            <button id="layerBtnSatellite" onclick="setMapLayer('satellite')" class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 text-white bg-sky-600 shadow">
                                <i class="bi bi-globe-americas"></i>
                                <span>Satellite</span>
                            </button>
                            <button id="layerBtnStreets" onclick="setMapLayer('streets')" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1.5">
                                <i class="bi bi-map"></i>
                                <span>Streets</span>
                            </button>
                            <button id="layerBtnResort" onclick="focusResortCenter()" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold text-amber-300 hover:text-amber-200 transition flex items-center gap-1.5">
                                <i class="bi bi-crosshair"></i>
                                <span>Center</span>
                            </button>
                        </div>
                    </div>

                    {{-- Floating Map Location Card (Bottom Left) --}}
                    <div class="absolute bottom-4 left-4 z-20 max-w-sm pointer-events-none">
                        <div class="glass-panel p-3.5 rounded-2xl shadow-2xl pointer-events-auto border border-white/15">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-[11px] font-extrabold uppercase tracking-wider text-sky-400">Resort Coordinates</div>
                                <span class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full font-bold">Live GPS</span>
                            </div>
                            <div class="text-white font-bold text-sm">Talisay Beach Resort</div>
                            <div class="text-xs text-slate-300">Barangay Maslug, Baybay City, Leyte, Philippines</div>
                            <div class="mt-2.5 pt-2 border-t border-white/10 flex items-center justify-between gap-2">
                                <div class="text-[11px] font-mono text-slate-400">Lat: 10.5807° N, Lon: 124.7656° E</div>
                                <a href="https://www.google.com/maps/search/?api=1&query=10.5807,124.7656" target="_blank" class="text-xs font-bold text-sky-400 hover:text-sky-300 no-underline flex items-center gap-1">
                                    <span>Google Maps</span>
                                    <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Column 3: Full Video Walkthrough Player --}}
            <div id="videoCol" class="w-full h-full relative hidden bg-slate-950 flex flex-col justify-center items-center p-4">
                <div class="w-full max-w-4xl bg-black rounded-2xl overflow-hidden shadow-2xl border border-white/15 relative">
                    <video id="resortTourVideo" controls class="w-full h-auto max-h-[60vh] object-contain" preload="metadata" poster="{{ asset('images/hero-landing.jpg') }}">
                        <source src="{{ asset('videos/virtual-tour.mp4') }}" type="video/mp4">
                        Your browser does not support HTML5 video streaming.
                    </video>
                    
                    {{-- Video Tour Footer Navigation --}}
                    <div class="p-4 bg-slate-900 border-t border-white/10 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h4 class="text-white font-bold text-sm mb-0.5 flex items-center gap-2">
                                <i class="bi bi-film text-sky-400"></i>
                                Talisay Beach Resort Official Video Walkthrough
                            </h4>
                            <p class="text-slate-400 text-xs mb-0">High-definition tour of the resort beachfront, amenities, and ocean views.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="switchViewMode('pano')" class="bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs px-4 py-2 rounded-xl transition shadow flex items-center gap-1.5">
                                <i class="bi bi-camera-video-fill"></i>
                                <span>Switch to 360° Panorama</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Bottom Scene Selection Carousel / Navigation Rail --}}
        <section class="mt-4">
            <div class="flex items-center justify-between mb-2.5">
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-white text-sm sm:text-base mb-0">Resort Viewpoints &amp; Locations</h3>
                    <span class="text-xs bg-white/10 text-sky-300 font-semibold px-2 py-0.5 rounded-full border border-white/10">
                        {{ $assets->count() }} Available Scenes
                    </span>
                </div>
                <div class="text-xs text-slate-400 flex items-center gap-1">
                    <i class="bi bi-cursor-fill text-sky-400 text-xs"></i>
                    <span>Click any card or map pin to jump</span>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                @php
                    $sceneMeta = [
                        1 => ['icon' => 'bi-door-open-fill', 'badge' => 'Main Gate & Reception', 'color' => 'from-sky-500 to-blue-600', 'lat' => 10.5812, 'lng' => 124.7654],
                        2 => ['icon' => 'bi-house-heart-fill', 'badge' => 'Beachfront Cottages', 'color' => 'from-teal-500 to-emerald-600', 'lat' => 10.5808, 'lng' => 124.7650],
                        3 => ['icon' => 'bi-cup-hot-fill', 'badge' => 'Dining & Beach Bar', 'color' => 'from-amber-500 to-orange-600', 'lat' => 10.5805, 'lng' => 124.7648],
                        4 => ['icon' => 'bi-stars', 'badge' => 'Premium Cabin Suites', 'color' => 'from-purple-500 to-indigo-600', 'lat' => 10.5801, 'lng' => 124.7645],
                        5 => ['icon' => 'bi-water', 'badge' => 'Coral Reef & Snorkeling', 'color' => 'from-cyan-500 to-blue-600', 'lat' => 10.5798, 'lng' => 124.7638],
                    ];
                @endphp

                @foreach($assets as $idx => $asset)
                @php
                    $meta = $sceneMeta[$asset->id] ?? [
                        'icon' => 'bi-geo-alt-fill',
                        'badge' => 'Resort Area',
                        'color' => 'from-sky-500 to-blue-600',
                        'lat' => 10.5807 + ($idx * 0.0003),
                        'lng' => 124.7656 - ($idx * 0.0003)
                    ];
                @endphp
                <div id="sceneCard_{{ $asset->id }}" onclick="loadPanoramaScene('scene_{{ $asset->id }}', true)" class="scene-card group relative bg-slate-900/90 hover:bg-slate-800/90 border border-white/10 hover:border-sky-400/80 rounded-2xl p-3 cursor-pointer transition-all duration-300 shadow-md hover:shadow-sky-500/10 hover:-translate-y-1 overflow-hidden">
                    {{-- Active Glowing Pill --}}
                    <div class="active-indicator absolute top-2 right-2 hidden">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399] animate-pulse"></span>
                    </div>

                    {{-- Gradient Icon Header --}}
                    <div class="h-16 w-full rounded-xl bg-gradient-to-br {{ $meta['color'] }} flex items-center justify-center mb-2.5 shadow-inner relative overflow-hidden group-hover:scale-[1.02] transition">
                        <i class="bi {{ $meta['icon'] }} text-white text-2xl drop-shadow-md"></i>
                        <span class="absolute bottom-1 right-2 text-[10px] font-extrabold text-white/70 font-mono">#{{ $asset->id }}</span>
                    </div>

                    {{-- Title & Details --}}
                    <div>
                        <div class="text-[10px] font-extrabold uppercase text-sky-400 tracking-wider mb-0.5">{{ $meta['badge'] }}</div>
                        <h4 class="text-white font-bold text-xs sm:text-sm leading-tight mb-1 truncate group-hover:text-sky-300 transition">{{ $asset->title }}</h4>
                        <p class="text-slate-400 text-[11px] line-clamp-1 leading-snug mb-1.5">{{ $asset->description }}</p>
                        <div class="flex items-center justify-between pt-1 border-t border-white/5 text-[10px] text-slate-500 font-mono">
                            <span><i class="bi bi-geo-alt-fill"></i> {{ number_format($meta['lat'], 4) }}, {{ number_format($meta['lng'], 4) }}</span>
                            <span class="text-sky-400 font-bold group-hover:underline">Explore &rarr;</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

    </main>

    {{-- Footer --}}
    <footer class="mt-auto py-3 px-6 border-t border-white/10 bg-slate-950 text-center text-xs text-slate-500 flex flex-wrap items-center justify-between gap-2">
        <div>
            &copy; {{ date('Y') }} <span class="font-bold text-slate-400">Talisay Beach Resort Smart Tourism System</span>. All rights reserved.
        </div>
        <div class="flex items-center gap-4 text-slate-400">
            <span>Barangay Maslug, Baybay City, Leyte</span>
            <span class="text-slate-600">&bull;</span>
            <a href="https://www.google.com/maps/dir/?api=1&destination=10.5807,124.7656" target="_blank" class="text-sky-400 hover:text-sky-300 no-underline">Get Directions</a>
        </div>
    </footer>

    {{-- Comprehensive JavaScript Logic --}}
    <script>
        // ── Scene Data & Hotspots Definition ──
        const tourScenes = {};
        const sceneLocations = {
            @foreach($assets as $asset)
            'scene_{{ $asset->id }}': {
                id: {{ $asset->id }},
                key: 'scene_{{ $asset->id }}',
                title: {{ Js::from($asset->title) }},
                description: {{ Js::from($asset->description) }},
                lat: {{ $sceneMeta[$asset->id]['lat'] ?? (10.5807 + ($loop->index * 0.0003)) }},
                lng: {{ $sceneMeta[$asset->id]['lng'] ?? (124.7656 - ($loop->index * 0.0003)) }},
                icon: "{{ $sceneMeta[$asset->id]['icon'] ?? 'bi-geo-alt-fill' }}"
            },
            @endforeach
        };

        // Hotspot target resolution mapping
        const slugToSceneId = {
            'main-entrance': 'scene_1',
            'cottage-area': 'scene_2',
            'restaurant': 'scene_3',
            'cabin-suite': 'scene_4',
            'snorkeling': 'scene_5',
            '1': 'scene_1',
            '2': 'scene_2',
            '3': 'scene_3',
            '4': 'scene_4',
            '5': 'scene_5'
        };

        // Build database-driven multi-scene configuration array for Pannellum
        @foreach($assets as $asset)
        tourScenes['scene_{{ $asset->id }}'] = {
            title: {{ Js::from($asset->title) }},
            type: "equirectangular",
            panorama: "{{ $asset->panorama_path ? (Str::startsWith($asset->panorama_path, 'http') ? $asset->panorama_path : Storage::url($asset->panorama_path)) : '' }}",
            autoLoad: true,
            autoRotate: -1.5,
            compass: true,
            hotspots: [
                @if($asset->hotspots)
                @foreach($asset->hotspots as $hotspot)
                @php
                    $rawTarget = $hotspot['sceneId'] ?? '1';
                    $targetScene = 'scene_' . $rawTarget;
                    if ($rawTarget === 'cottage-area') $targetScene = 'scene_2';
                    elseif ($rawTarget === 'restaurant') $targetScene = 'scene_3';
                    elseif ($rawTarget === 'main-entrance') $targetScene = 'scene_1';
                    elseif (is_numeric($rawTarget)) $targetScene = 'scene_' . $rawTarget;
                @endphp
                {
                    pitch: {{ $hotspot['pitch'] ?? 0 }},
                    yaw: {{ $hotspot['yaw'] ?? 0 }},
                    type: "{{ ($hotspot['type'] ?? 'info') === 'scene' ? 'scene' : 'info' }}",
                    text: {{ Js::from($hotspot['text'] ?? '') }},
                    @if(($hotspot['type'] ?? 'info') === 'scene')
                    sceneId: "{{ $targetScene }}",
                    @endif
                },
                @endforeach
                @endif
            ]
        };

        // Duplicate scene entries with named slugs as aliases
        @if($asset->id == 1) tourScenes['main-entrance'] = tourScenes['scene_1']; @endif
        @if($asset->id == 2) tourScenes['cottage-area'] = tourScenes['scene_2']; @endif
        @if($asset->id == 3) tourScenes['restaurant'] = tourScenes['scene_3']; @endif
        @if($asset->id == 4) tourScenes['cabin-suite'] = tourScenes['scene_4']; @endif
        @if($asset->id == 5) tourScenes['snorkeling'] = tourScenes['scene_5']; @endif
        @endforeach

        let viewer = null;
        let currentSceneKey = 'scene_{{ $assets->first()->id ?? 1 }}';
        let isAutoRotating = true;
        let activeViewMode = 'pano';

        // ── Initialize Pannellum Viewer ──
        @if($assets->count() > 0)
        try {
            viewer = pannellum.viewer('panoramaViewer', {
                default: {
                    firstScene: currentSceneKey,
                    author: "Talisay Beach Resort",
                    sceneFadeDuration: 1000,
                    autoLoad: true,
                    autoRotate: -1.5,
                    showControls: false // We use our custom modern controls
                },
                scenes: tourScenes
            });

            // Listen to scene load events to sync map and UI
            viewer.on('load', function() {
                const activeId = viewer.getScene();
                const canonicalKey = slugToSceneId[activeId] || activeId;
                syncActiveSceneUI(canonicalKey);
            });
        } catch (e) {
            console.error("Pannellum initialization error:", e);
        }
        @endif

        // ── Initialize Leaflet Interactive Maps ──
        const resortCenter = [10.5807, 124.7656]; // Brgy. Maslug, Baybay City, Leyte
        let mainMap = null;
        let miniMap = null;
        let mapMarkers = {};
        let miniMapMarkers = {};
        let satelliteTileLayer = null;
        let streetTileLayer = null;

        function initLeafletMaps() {
            // Main Map Layers
            satelliteTileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri &mdash; Baybay City, Leyte Coastline',
                maxZoom: 19
            });

            streetTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            });

            // Initialize Main Map
            mainMap = L.map('leafletMap', {
                center: resortCenter,
                zoom: 17,
                layers: [satelliteTileLayer],
                zoomControl: true
            });

            // Initialize Mini-Map
            const miniSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19
            });
            miniMap = L.map('miniMapLeaflet', {
                center: resortCenter,
                zoom: 16,
                layers: [miniSatellite],
                zoomControl: false,
                attributionControl: false
            });

            // Add pins for each tour scene
            Object.keys(sceneLocations).forEach((key) => {
                const loc = sceneLocations[key];

                // Create custom HTML Radar Pin Marker
                const customIcon = L.divIcon({
                    className: 'custom-map-icon',
                    html: `<div id="pin_${key}" class="radar-pin ${key === currentSceneKey ? 'active' : ''}" onclick="loadPanoramaScene('${key}', true)"><i class="bi ${loc.icon}"></i></div>`,
                    iconSize: [38, 38],
                    iconAnchor: [19, 19]
                });

                // Main Map Marker & Popup
                const marker = L.marker([loc.lat, loc.lng], { icon: customIcon }).addTo(mainMap);
                marker.bindPopup(`
                    <div style="min-width: 200px; padding: 4px;">
                        <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #38bdf8; margin-bottom: 2px;">Tour Viewpoint</div>
                        <h5 style="margin: 0 0 4px; font-size: 14px; font-weight: bold; color: #ffffff;">${loc.title}</h5>
                        <p style="margin: 0 0 10px; font-size: 11px; color: #94a3b8; line-height: 1.4;">${loc.description}</p>
                        <button onclick="loadPanoramaScene('${key}', true); switchViewMode('pano');" style="width: 100%; background: #0284c7; color: white; border: none; padding: 6px 12px; border-radius: 8px; font-weight: bold; font-size: 11px; cursor: pointer;">
                            <i class="bi bi-camera-video-fill"></i> View in 360° Panorama
                        </button>
                    </div>
                `);
                mapMarkers[key] = marker;

                // Mini-Map Marker
                const miniIcon = L.divIcon({
                    className: 'mini-map-icon',
                    html: `<div id="minipin_${key}" class="radar-pin ${key === currentSceneKey ? 'active' : ''}" style="width:24px;height:24px;font-size:11px;" onclick="loadPanoramaScene('${key}', true)"><i class="bi ${loc.icon}"></i></div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });
                const miniMarker = L.marker([loc.lat, loc.lng], { icon: miniIcon }).addTo(miniMap);
                miniMapMarkers[key] = miniMarker;
            });
        }

        // Initialize maps on page load
        document.addEventListener('DOMContentLoaded', function() {
            initLeafletMaps();
            syncActiveSceneUI(currentSceneKey);
        });

        // ── Scene Transition & UI Sync ──
        function loadPanoramaScene(sceneKey, panMap = true) {
            const resolvedKey = slugToSceneId[sceneKey] || sceneKey;
            currentSceneKey = resolvedKey;

            if (viewer) {
                viewer.loadScene(resolvedKey);
            }
            syncActiveSceneUI(resolvedKey, panMap);
        }

        function syncActiveSceneUI(sceneKey, panMap = true) {
            const loc = sceneLocations[sceneKey];
            if (!loc) return;

            // 1. Update Title & Info Badges
            const titleEl = document.getElementById('sceneInfoTitle');
            const descEl = document.getElementById('sceneInfoDesc');
            const coordsEl = document.getElementById('sceneBadgeCoordinates');
            if (titleEl) titleEl.textContent = loc.title;
            if (descEl) descEl.textContent = loc.description;
            if (coordsEl) coordsEl.textContent = `${loc.lat.toFixed(4)}°N, ${loc.lng.toFixed(4)}°E`;

            // 2. Update Scene Selector Rail Cards
            document.querySelectorAll('.scene-card').forEach(card => {
                card.classList.remove('border-sky-500', 'bg-slate-800/90', 'ring-2', 'ring-sky-500/40');
                const indicator = card.querySelector('.active-indicator');
                if (indicator) indicator.classList.add('hidden');
            });
            const activeCard = document.getElementById(`sceneCard_${loc.id}`);
            if (activeCard) {
                activeCard.classList.add('border-sky-500', 'bg-slate-800/90', 'ring-2', 'ring-sky-500/40');
                const indicator = activeCard.querySelector('.active-indicator');
                if (indicator) indicator.classList.remove('hidden');
            }

            // 3. Update Map Pins (Radar Active Pulse)
            document.querySelectorAll('.radar-pin').forEach(pin => pin.classList.remove('active'));
            const activePin = document.getElementById(`pin_${sceneKey}`);
            if (activePin) activePin.classList.add('active');

            const activeMiniPin = document.getElementById(`minipin_${sceneKey}`);
            if (activeMiniPin) activeMiniPin.classList.add('active');

            // 4. Pan Maps to Active Pin
            if (panMap && mainMap && mapMarkers[sceneKey]) {
                mainMap.panTo([loc.lat, loc.lng], { animate: true, duration: 1.0 });
            }
            if (miniMap) {
                miniMap.panTo([loc.lat, loc.lng], { animate: true });
            }
        }

        // ── View Mode Switcher ──
        function switchViewMode(mode) {
            activeViewMode = mode;
            const viewport = document.getElementById('tourViewport');
            const panoCol = document.getElementById('panoramaCol');
            const mapCol = document.getElementById('mapCol');
            const videoCol = document.getElementById('videoCol');
            const miniMapWidget = document.getElementById('miniMapWidget');

            // Reset tab button states
            ['tabBtnPano', 'tabBtnSplit', 'tabBtnMap', 'tabBtnVideo'].forEach(btnId => {
                const btn = document.getElementById(btnId);
                btn.classList.remove('bg-sky-600', 'text-white', 'shadow');
                btn.classList.add('text-slate-400');
            });

            // Pause video if playing when leaving video mode
            const videoPlayer = document.getElementById('resortTourVideo');
            if (mode !== 'video' && videoPlayer) {
                videoPlayer.pause();
            }

            if (mode === 'pano') {
                document.getElementById('tabBtnPano').classList.add('bg-sky-600', 'text-white', 'shadow');
                document.getElementById('tabBtnPano').classList.remove('text-slate-400');
                viewport.classList.remove('split-active');
                panoCol.classList.remove('hidden');
                panoCol.style.width = '100%';
                mapCol.classList.add('hidden');
                videoCol.classList.add('hidden');
                if (miniMapWidget) miniMapWidget.classList.remove('hidden');
            } else if (mode === 'split') {
                document.getElementById('tabBtnSplit').classList.add('bg-sky-600', 'text-white', 'shadow');
                document.getElementById('tabBtnSplit').classList.remove('text-slate-400');
                viewport.classList.add('split-active');
                panoCol.classList.remove('hidden');
                mapCol.classList.remove('hidden');
                videoCol.classList.add('hidden');
                if (miniMapWidget) miniMapWidget.classList.add('hidden'); // hidden in split because full map is visible!
                setTimeout(() => {
                    if (mainMap) mainMap.invalidateSize();
                }, 150);
            } else if (mode === 'map') {
                document.getElementById('tabBtnMap').classList.add('bg-sky-600', 'text-white', 'shadow');
                document.getElementById('tabBtnMap').classList.remove('text-slate-400');
                viewport.classList.remove('split-active');
                panoCol.classList.add('hidden');
                mapCol.classList.remove('hidden');
                mapCol.style.width = '100%';
                videoCol.classList.add('hidden');
                if (miniMapWidget) miniMapWidget.classList.add('hidden');
                setTimeout(() => {
                    if (mainMap) {
                        mainMap.invalidateSize();
                        focusResortCenter();
                    }
                }, 150);
            } else if (mode === 'video') {
                document.getElementById('tabBtnVideo').classList.add('bg-sky-600', 'text-white', 'shadow');
                document.getElementById('tabBtnVideo').classList.remove('text-slate-400');
                viewport.classList.remove('split-active');
                panoCol.classList.add('hidden');
                mapCol.classList.add('hidden');
                videoCol.classList.remove('hidden');
                if (miniMapWidget) miniMapWidget.classList.add('hidden');
                if (videoPlayer) {
                    videoPlayer.play().catch(e => console.log('Autoplay prevented:', e));
                }
            }
        }

        // ── Map Controls & Helpers ──
        function setMapLayer(layerType) {
            const btnSat = document.getElementById('layerBtnSatellite');
            const btnStr = document.getElementById('layerBtnStreets');

            if (layerType === 'satellite') {
                mainMap.removeLayer(streetTileLayer);
                mainMap.addLayer(satelliteTileLayer);
                btnSat.classList.add('bg-sky-600', 'text-white', 'shadow');
                btnSat.classList.remove('text-slate-400');
                btnStr.classList.remove('bg-sky-600', 'text-white', 'shadow');
                btnStr.classList.add('text-slate-400');
            } else {
                mainMap.removeLayer(satelliteTileLayer);
                mainMap.addLayer(streetTileLayer);
                btnStr.classList.add('bg-sky-600', 'text-white', 'shadow');
                btnStr.classList.remove('text-slate-400');
                btnSat.classList.remove('bg-sky-600', 'text-white', 'shadow');
                btnSat.classList.add('text-slate-400');
            }
        }

        function focusResortCenter() {
            if (mainMap) {
                mainMap.flyTo(resortCenter, 17, { duration: 1.2 });
            }
        }

        function toggleMiniMapCollapse() {
            const widget = document.getElementById('miniMapWidget');
            const icon = document.getElementById('miniMapToggleIcon');
            if (widget.classList.contains('minimized')) {
                widget.classList.remove('minimized');
                icon.className = 'bi bi-dash text-sm';
                setTimeout(() => { if (miniMap) miniMap.invalidateSize(); }, 300);
            } else {
                widget.classList.add('minimized');
                icon.className = 'bi bi-plus text-sm';
            }
        }

        // ── 360 Viewer Controls ──
        function toggleAutoRotate() {
            if (!viewer) return;
            isAutoRotating = !isAutoRotating;
            viewer.setAutoRotate(isAutoRotating ? -1.5 : false);
            const icon = document.getElementById('iconAutoRotate');
            if (icon) {
                icon.className = isAutoRotating ? 'bi bi-pause-fill text-lg' : 'bi bi-play-fill text-lg';
            }
        }

        function togglePanoFullscreen() {
            const elem = document.getElementById('tourViewport');
            if (!document.fullscreenElement) {
                elem.requestFullscreen().catch(err => alert(`Error enabling fullscreen: ${err.message}`));
            } else {
                document.exitFullscreen();
            }
        }
    </script>
</body>
</html>