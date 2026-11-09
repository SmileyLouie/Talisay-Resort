@extends('layouts.public')

@section('title')
360° Virtual Tour – {{ ($resortInfo['name'] ?? 'Talisay Beach Resort') }}
@endsection
@section('meta_description', 'Walk Talisay Beach Resort in 360°, open the resort map, or watch the video tour.')
@section('body_class', 'vt-page vt-portal')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>

        :root {
            --vt-bg: #06121f;
            --vt-panel: rgba(8, 22, 40, .82);
            --vt-card: #0b1d33;
            --vt-line: rgba(255, 255, 255, .1);
            --vt-text: #f1f5f9;
            --vt-muted: #94a3b8;
            --vt-blue: #0ea5e9;
            --vt-blue-deep: #0284c7;
            --vt-sand: #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body.resort-page.vt-page { min-height: 100vh; display: block; }
        body.vt-page .hidden { display: none !important; }
        body.vt-page a { text-decoration: none; }
        body.vt-page button { font-family: inherit; cursor: pointer; }
        body.vt-page :focus-visible { outline: 2px solid #7dd3fc; outline-offset: 2px; }

        /* Portal tour chrome under the public nav */
        body.resort-page.vt-page {
            background: #f7fbfd;
            color: #0f172a;
            overflow-x: hidden;
        }
        .vt-toolbar {
            max-width: 1280px;
            margin: 0 auto;
            padding: 100px 24px 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .vt-toolbar-copy { min-width: 0; }
        .vt-toolbar-copy .lp-label { margin-bottom: 4px; }
        .vt-toolbar-copy h1 {
            margin: 0;
            font-family: 'Playfair Display', Georgia, serif;
            font-size: clamp(26px, 3vw, 34px);
            color: #082f49;
            line-height: 1.15;
        }
        .vt-toolbar-copy p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
            max-width: 46ch;
        }
        .vt-tabs {
            display: inline-flex;
            gap: 2px;
            padding: 4px;
            border: 1px solid #d7e6f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 20px rgba(8, 47, 73, .06);
        }
        .vt-tab {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 36px;
            padding: 0 14px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            transition: background .15s, color .15s;
        }
        .vt-tab:hover { color: #082f49; background: #f0f9ff; }
        .vt-tab.is-active { background: #0284c7; color: #fff; box-shadow: 0 4px 12px rgba(2, 132, 199, .28); }
        .vt-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .vt-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 38px;
            padding: 0 14px;
            border: 1px solid #d7e6f0;
            border-radius: 999px;
            background: #fff;
            color: #082f49;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            transition: background .15s, border-color .15s, color .15s;
        }
        .vt-btn:hover { background: #f0f9ff; border-color: #bae6fd; color: #0284c7; }
        .vt-btn.is-primary { border-color: #082f49; background: #082f49; color: #fff; }
        .vt-btn.is-primary:hover { background: #0c4a6e; }
        .vt-btn i { font-size: 14px; }

        /* Stage */
        .vt-main { width: 100%; max-width: 1280px; margin: 0 auto; padding: 16px 24px 40px; }
        .viewport-wrapper {
            position: relative;
            width: 100%;
            height: calc(100vh - 230px);
            min-height: 460px;
            max-height: 780px;
            overflow: hidden;
            border-radius: 18px;
            background: #020b17;
            box-shadow: 0 24px 48px -20px rgba(0, 0, 0, .8), 0 0 0 1px var(--vt-line);
        }
        #panoramaCol, #mapCol, #videoCol { position: relative; width: 100%; height: 100%; }
        #panoramaContainer, #mapContainer { position: relative; width: 100%; height: 100%; background: #030d1a; }
        #panoramaViewer, #leafletMap { width: 100%; height: 100%; }
        #leafletMap { z-index: 10; }
        #flatSceneImage { position: absolute; inset: 0; z-index: 6; width: 100%; height: 100%; object-fit: contain; background: #030d1a; }
        .pnlm-panorama-info { display: none !important; }
        .pnlm-compass { top: 16px !important; right: 16px !important; bottom: auto !important; width: 44px !important; height: 44px !important; background-size: 44px !important; opacity: .85; }

        .viewport-wrapper.split-active { display: flex; }
        .split-active #panoramaCol { flex: 0 0 56%; width: 56%; border-right: 1px solid rgba(255, 255, 255, .14); }
        .split-active #mapCol { flex: 0 0 44%; width: 44%; }

        .vt-panel {
            background: var(--vt-panel);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--vt-line);
            box-shadow: 0 12px 30px rgba(0, 0, 0, .35);
        }
        .vt-info {
            position: absolute;
            top: 16px;
            left: 16px;
            z-index: 20;
            max-width: 360px;
            padding: 14px 16px;
            border-radius: 14px;
        }
        .vt-info-step { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #7dd3fc; }
        .vt-info-step::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 3px rgba(52, 211, 153, .2); }
        .vt-info h2 { margin: 0; font-size: 18px; font-weight: 700; line-height: 1.3; color: #fff; }
        .vt-info p {
            margin-top: 4px;
            font-size: 13px;
            line-height: 1.5;
            color: #cbd5e1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .vt-info p:empty { display: none; }

        .vt-controls { position: absolute; left: 16px; bottom: 16px; z-index: 20; display: flex; align-items: center; gap: 8px; }
        .vt-ctrl-group { display: flex; gap: 2px; padding: 4px; border-radius: 12px; }
        .vt-ctrl {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: #e2e8f0;
            font-size: 15px;
            transition: background .15s;
        }
        .vt-ctrl:hover { background: rgba(255, 255, 255, .12); color: #fff; }
        .vt-ctrl-map {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 44px;
            padding: 0 14px;
            border-radius: 12px;
            color: #e2e8f0;
            font-size: 13px;
            font-weight: 600;
        }
        .vt-ctrl-map:hover { color: #fff; background: rgba(2, 132, 199, .85); }

        .minimap-radar {
            position: absolute;
            right: 16px;
            bottom: 16px;
            z-index: 30;
            width: 240px;
            height: 176px;
            overflow: hidden;
            border-radius: 14px;
            transition: width .25s ease, height .25s ease;
        }
        .vt-minimap-head {
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 6px 0 12px;
            border-bottom: 1px solid var(--vt-line);
            font-size: 12px;
            font-weight: 600;
            color: #e2e8f0;
        }
        .vt-minimap-head span { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
        .vt-tab-short { display: none; }
        .vt-minimap-head span i { color: var(--vt-sand); }
        .vt-minimap-head div { display: flex; gap: 2px; }
        .vt-minimap-head button {
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: var(--vt-muted);
            font-size: 13px;
        }
        .vt-minimap-head button:hover { background: rgba(255, 255, 255, .1); color: #fff; }
        #miniMapLeaflet { width: 100%; height: calc(100% - 32px); }
        .minimap-radar.minimized { width: 150px; height: 32px; }
        .minimap-radar.minimized #miniMapLeaflet { display: none; }
        .minimap-radar.minimized .vt-minimap-head { border-bottom: 0; }

        .vt-empty {
            position: absolute;
            inset: 0;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 24px;
            text-align: center;
            background: #071526;
        }
        .vt-empty i { font-size: 44px; color: #38bdf8; }
        .vt-empty h3 { font-size: 18px; font-weight: 700; }
        .vt-empty p { max-width: 380px; font-size: 13px; color: var(--vt-muted); }

        /* Map */
        .radar-pin {
            position: relative;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            border-radius: 50%;
            background: var(--vt-blue-deep);
            color: #fff;
            font-size: 15px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, .6);
            cursor: pointer;
            transition: transform .2s cubic-bezier(.34, 1.56, .64, 1);
        }
        .radar-pin:hover { transform: scale(1.15); }
        .radar-pin.active { background: var(--vt-sand); box-shadow: 0 0 0 4px rgba(245, 158, 11, .35), 0 6px 18px rgba(245, 158, 11, .6); }
        .radar-pin.active::after {
            content: "";
            position: absolute;
            inset: -8px;
            border: 2px solid var(--vt-sand);
            border-radius: 50%;
            animation: vtPulse 1.8s infinite ease-out;
        }
        @keyframes vtPulse {
            0% { transform: scale(.7); opacity: 1; }
            100% { transform: scale(1.8); opacity: 0; }
        }
        .vt-map-layers { position: absolute; top: 16px; right: 16px; z-index: 20; display: flex; gap: 2px; padding: 4px; border-radius: 12px; }
        .vt-layer {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 32px;
            padding: 0 12px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: var(--vt-muted);
            font-size: 12.5px;
            font-weight: 600;
        }
        .vt-layer:hover { color: #fff; }
        .vt-layer.is-active { background: var(--vt-blue-deep); color: #fff; }
        .vt-layer.is-center { color: #fcd34d; }
        .vt-place {
            position: absolute;
            left: 16px;
            bottom: 16px;
            z-index: 20;
            max-width: 320px;
            padding: 14px 16px;
            border-radius: 14px;
        }
        .vt-place strong { display: block; font-size: 14px; color: #fff; }
        .vt-place span { display: block; margin-top: 2px; font-size: 12.5px; color: #cbd5e1; }
        .vt-place a { display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; font-size: 12.5px; font-weight: 600; color: #7dd3fc; }
        .vt-place a:hover { color: #fff; }
        .leaflet-popup-content-wrapper { background: #0b1d33; color: #fff; border: 1px solid rgba(255, 255, 255, .14); border-radius: 12px; box-shadow: 0 12px 28px rgba(0, 0, 0, .5); }
        .leaflet-popup-tip { background: #0b1d33; }
        .vt-pop { min-width: 190px; }
        .vt-pop small { display: block; margin-bottom: 2px; font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #7dd3fc; }
        .vt-pop strong { display: block; font-size: 14px; }
        .vt-pop p { margin: 4px 0 10px !important; font-size: 12px; line-height: 1.4; color: var(--vt-muted); }
        .vt-pop button { width: 100%; padding: 7px 12px; border: 0; border-radius: 8px; background: var(--vt-blue-deep); color: #fff; font-size: 12px; font-weight: 600; }

        /* Video */
        #videoCol { display: flex; align-items: center; justify-content: center; padding: 20px; background: #030b16; }
        .vt-video { width: 100%; max-width: 960px; overflow: hidden; border: 1px solid var(--vt-line); border-radius: 16px; background: #000; }
        #resortTourVideo { display: block; width: 100%; max-height: calc(100vh - 380px); min-height: 240px; object-fit: contain; background: #000; }
        #tourVideoEmpty { padding: 64px 24px; text-align: center; font-size: 14px; color: #cbd5e1; }
        .vt-video-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; background: var(--vt-card); border-top: 1px solid var(--vt-line); }
        .vt-video-foot h4 { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; color: #fff; }
        .vt-video-foot h4 i { color: #38bdf8; }
        .vt-video-foot > div { flex: 1 1 260px; min-width: 0; }
        .vt-video-foot p {
            margin-top: 2px;
            font-size: 12.5px;
            color: var(--vt-muted);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Scenes */
        .vt-scenes { margin-top: 24px; }
        .vt-scenes-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 8px; margin-bottom: 12px; }
        .vt-scenes-head h3 { display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 700; color: #fff; }
        .vt-scenes-head h3 span { padding: 2px 9px; border: 1px solid var(--vt-line); border-radius: 999px; background: rgba(255, 255, 255, .05); font-size: 12px; font-weight: 600; color: #7dd3fc; }
        .vt-scenes-head p { font-size: 13px; color: var(--vt-muted); }
        .vt-scene-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; }
        .vt-scene {
            display: flex;
            flex-direction: column;
            padding: 0;
            overflow: hidden;
            border: 1px solid var(--vt-line);
            border-radius: 14px;
            background: var(--vt-card);
            color: inherit;
            text-align: left;
            transition: border-color .2s, transform .2s, box-shadow .2s;
        }
        .vt-scene:hover { transform: translateY(-2px); border-color: rgba(56, 189, 248, .55); box-shadow: 0 12px 24px rgba(0, 0, 0, .35); }
        .vt-scene.is-active { border-color: var(--vt-blue); box-shadow: 0 0 0 2px rgba(14, 165, 233, .35); }
        .vt-scene-media { position: relative; display: block; aspect-ratio: 16 / 10; overflow: hidden; background: linear-gradient(135deg, #0369a1, #0f172a); }
        .vt-scene-media img, .vt-scene-media video { width: 100%; height: 100%; object-fit: cover; transition: transform .35s; }
        .vt-scene:hover .vt-scene-media img, .vt-scene:hover .vt-scene-media video { transform: scale(1.05); }
        .vt-scene-media > i { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 28px; color: rgba(255, 255, 255, .85); }
        .vt-scene-num {
            position: absolute;
            top: 8px;
            left: 8px;
            min-width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 7px;
            border-radius: 7px;
            background: rgba(6, 18, 31, .78);
            font-size: 12px;
            font-weight: 700;
            color: #fff;
        }
        .vt-scene-kind {
            position: absolute;
            right: 8px;
            bottom: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 999px;
            background: rgba(6, 18, 31, .78);
            font-size: 11px;
            font-weight: 600;
            color: #e2e8f0;
        }
        .vt-scene-now {
            position: absolute;
            top: 8px;
            right: 8px;
            display: none;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 999px;
            background: var(--vt-blue-deep);
            font-size: 11px;
            font-weight: 700;
            color: #fff;
        }
        .vt-scene.is-active .vt-scene-now { display: inline-flex; }
        .vt-scene-body { display: block; padding: 12px 14px 14px; }
        .vt-scene-body strong { display: block; overflow: hidden; font-size: 14px; font-weight: 700; color: #fff; text-overflow: ellipsis; white-space: nowrap; }
        .vt-scene.is-active .vt-scene-body strong { color: #7dd3fc; }
        .vt-scene-body span {
            display: -webkit-box;
            margin-top: 4px;
            font-size: 12.5px;
            line-height: 1.45;
            color: var(--vt-muted);
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .vt-foot { border-top: 1px solid var(--vt-line); }
        .vt-foot-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 16px 24px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 12.5px;
            color: #64748b;
        }
        .vt-foot-inner strong { color: #cbd5e1; }
        .vt-foot-inner a { color: #7dd3fc; font-weight: 600; }

        @media (max-width: 1100px) {
            .vt-scene-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 960px) {
            .vt-top-inner { grid-template-columns: 1fr auto; }
            .vt-tabs { grid-column: 1 / -1; order: 3; justify-self: center; }
            .vt-btn-label { display: none; }
            .viewport-wrapper.split-active { flex-direction: column; }
            .split-active #panoramaCol, .split-active #mapCol { flex: 1 1 50%; width: 100%; height: 50%; }
            .split-active #panoramaCol { border-right: 0; border-bottom: 1px solid rgba(255, 255, 255, .14); }
        }
        @media (max-width: 640px) {
            .vt-top-inner { padding: 10px 14px; }
            .vt-brand span { display: none; }
            .vt-tab { padding: 0 10px; font-size: 12px; }
            .vt-tab-long { display: none; }
            .vt-tab-short { display: inline; }
            .pnlm-compass { display: none !important; }
            .vt-main { width: 100%; max-width: 1280px; margin: 0 auto; padding: 16px 24px 40px; }
            .viewport-wrapper { height: calc(100vh - 250px); min-height: 400px; border-radius: 14px; }
            .vt-info { top: 10px; left: 10px; right: 10px; max-width: none; padding: 10px 12px; }
            .vt-info h2 { font-size: 15px; }
            .vt-controls { left: 10px; bottom: 10px; }
            .vt-ctrl-map span { display: none; }
            .minimap-radar { right: 10px; bottom: 66px; width: 150px; height: 120px; }
            .vt-scene-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        }
    
        .vt-scenes { margin-top: 22px; }
        .vt-scenes-head h3 { color: #082f49; }
        .vt-scenes-head p { color: #64748b; }
        .vt-scenes-head h3 span {
            display: inline-flex; align-items: center; margin-left: 8px;
            padding: 3px 10px; border-radius: 999px; background: #e0f2fe; color: #0284c7;
            font-size: 12px; font-weight: 800; font-family: Inter, sans-serif;
        }
        .vt-scene {
            background: #fff; border: 1px solid #d7e6f0; color: #0f172a;
            box-shadow: 0 8px 20px rgba(8, 47, 73, .05);
        }
        .vt-scene strong { color: #082f49; }
        .vt-scene span { color: #64748b; }
        @media (max-width: 900px) {
            .vt-toolbar { padding: 96px 16px 0; }
            .vt-main { padding: 14px 16px 32px; }
            .vt-toolbar-copy h1 { font-size: 24px; }
            .vt-tab-long { display: none; }
            .vt-btn-label { display: none; }
            .vt-btn { width: 38px; justify-content: center; padding: 0; }
            .vt-btn.is-primary { width: auto; padding: 0 14px; }
            .vt-btn.is-primary .vt-btn-label { display: inline; }
        }
</style>
@endpush

@section('content')
@php
        $openingScene = $assets->first(fn ($asset) => $asset->isPanorama()) ?? $assets->first();
        $sceneMeta = [
            1 => ['icon' => 'bi-door-open-fill', 'lat' => 10.5812, 'lng' => 124.7654],
            2 => ['icon' => 'bi-house-heart-fill', 'lat' => 10.5808, 'lng' => 124.7650],
            3 => ['icon' => 'bi-cup-hot-fill', 'lat' => 10.5805, 'lng' => 124.7648],
            4 => ['icon' => 'bi-stars', 'lat' => 10.5801, 'lng' => 124.7645],
            5 => ['icon' => 'bi-water', 'lat' => 10.5798, 'lng' => 124.7638],
        ];
        $metaFor = fn ($asset, $idx) => $sceneMeta[$asset->id] ?? [
            'icon' => 'bi-geo-alt-fill',
            'lat' => 10.5807 + ($idx * 0.0003),
            'lng' => 124.7656 - ($idx * 0.0003),
        ];
        $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=10.5807,124.7656';
    @endphp

    <div class="vt-toolbar">
        <div class="vt-toolbar-copy">
            <div class="lp-label">360° Virtual Tour</div>
            <h1>Explore the resort</h1>
            <p>Walk the grounds in 360°, open the resort map, or play the video tour — all on the client site.</p>
        </div>
        <nav class="vt-tabs" aria-label="Tour view">
            <button type="button" id="tabBtnPano" class="vt-tab is-active" onclick="switchViewMode('pano')">
                <i class="bi bi-badge-vr"></i><span>360° <span class="vt-tab-long">view</span></span>
            </button>
            <button type="button" id="tabBtnSplit" class="vt-tab" onclick="switchViewMode('split')">
                <i class="bi bi-layout-split"></i><span>Split<span class="vt-tab-long"> view</span></span>
            </button>
            <button type="button" id="tabBtnMap" class="vt-tab" onclick="switchViewMode('map')">
                <i class="bi bi-map"></i><span><span class="vt-tab-long">Resort map</span><span class="vt-tab-short">Map</span></span>
            </button>
            <button type="button" id="tabBtnVideo" class="vt-tab" onclick="switchViewMode('video')">
                <i class="bi bi-play-circle"></i><span>Video<span class="vt-tab-long"> tour</span></span>
            </button>
        </nav>
        <div class="vt-actions">
            <a href="{{ $directionsUrl }}" target="_blank" rel="noopener" class="vt-btn" title="Get directions in Google Maps">
                <i class="bi bi-signpost-2"></i><span class="vt-btn-label">Directions</span>
            </a>
            <a href="{{ route('public.booking') }}" class="vt-btn is-primary" data-book>
                <i class="bi bi-calendar-check"></i><span class="vt-btn-label">Book a stay</span>
            </a>
        </div>
    </div>

<main class="vt-main">
        <div class="viewport-wrapper" id="tourViewport">

            {{-- 360Â° view --}}
            <div id="panoramaCol">
                <div id="panoramaContainer">
                    <div id="panoramaViewer"></div>
                    <img id="flatSceneImage" alt="" class="hidden">

                    @if($assets->isEmpty())
                    <div class="vt-empty">
                        <i class="bi bi-badge-vr"></i>
                        <h3>The virtual tour is coming soon</h3>
                        <p>The resort hasn't published any tour scenes yet. Please check back later.</p>
                    </div>
                    @else
                    <div class="vt-info vt-panel">
                        <div class="vt-info-step">Scene <span id="sceneInfoIndex">{{ ($assets->search(fn ($a) => $a->id === $openingScene->id) ?: 0) + 1 }}</span> of {{ $assets->count() }}</div>
                        <h2 id="sceneInfoTitle">{{ $openingScene->title }}</h2>
                        <p id="sceneInfoDesc">{{ $openingScene->description }}</p>
                    </div>
                    @endif

                    <div class="vt-controls">
                        <div class="vt-ctrl-group vt-panel">
                            <button type="button" id="btnAutoRotate" class="vt-ctrl" onclick="toggleAutoRotate()" title="Pause or resume turning" aria-label="Pause or resume turning">
                                <i id="iconAutoRotate" class="bi bi-pause-fill"></i>
                            </button>
                            <button type="button" class="vt-ctrl" onclick="if (viewer) { viewer.setPitch(0); viewer.setYaw(0); }" title="Reset view" aria-label="Reset view">
                                <i class="bi bi-compass"></i>
                            </button>
                            <button type="button" class="vt-ctrl" onclick="if (viewer) viewer.setHfov(viewer.getHfov() - 15);" title="Zoom in" aria-label="Zoom in">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                            <button type="button" class="vt-ctrl" onclick="if (viewer) viewer.setHfov(viewer.getHfov() + 15);" title="Zoom out" aria-label="Zoom out">
                                <i class="bi bi-dash-lg"></i>
                            </button>
                            <button type="button" class="vt-ctrl" onclick="togglePanoFullscreen()" title="Full screen" aria-label="Full screen">
                                <i class="bi bi-arrows-fullscreen"></i>
                            </button>
                        </div>
                        <button type="button" class="vt-ctrl-map vt-panel" onclick="switchViewMode('split')">
                            <i class="bi bi-map"></i><span>Show on map</span>
                        </button>
                    </div>

                    <div id="miniMapWidget" class="minimap-radar vt-panel">
                        <div class="vt-minimap-head">
                            <span><i class="bi bi-geo-alt-fill"></i> Resort map</span>
                            <div>
                                <button type="button" onclick="switchViewMode('map')" title="Open the full map" aria-label="Open the full map">
                                    <i class="bi bi-arrows-angle-expand"></i>
                                </button>
                                <button type="button" onclick="toggleMiniMapCollapse()" title="Hide or show the map" aria-label="Hide or show the map">
                                    <i id="miniMapToggleIcon" class="bi bi-dash"></i>
                                </button>
                            </div>
                        </div>
                        <div id="miniMapLeaflet"></div>
                    </div>
                </div>
            </div>

            {{-- Resort map --}}
            <div id="mapCol" class="hidden">
                <div id="mapContainer">
                    <div id="leafletMap"></div>

                    <div class="vt-map-layers vt-panel">
                        <button type="button" id="layerBtnSatellite" class="vt-layer is-active" onclick="setMapLayer('satellite')">
                            <i class="bi bi-globe-americas"></i><span>Satellite</span>
                        </button>
                        <button type="button" id="layerBtnStreets" class="vt-layer" onclick="setMapLayer('streets')">
                            <i class="bi bi-map"></i><span>Streets</span>
                        </button>
                        <button type="button" class="vt-layer is-center" onclick="focusResortCenter()">
                            <i class="bi bi-crosshair"></i><span>Center</span>
                        </button>
                    </div>

                    <div class="vt-place vt-panel">
                        <strong>Talisay Beach Resort</strong>
                        <span>Brgy. Maslug, Baybay City, Leyte, Philippines</span>
                        <a href="{{ $directionsUrl }}" target="_blank" rel="noopener">
                            Get directions <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Video tour --}}
            <div id="videoCol" class="hidden">
                <div class="vt-video">
                    <video id="resortTourVideo" controls playsinline preload="metadata"></video>
                    <div id="tourVideoEmpty" class="hidden">
                        No tour video yet. Please check back later.
                    </div>
                    <div class="vt-video-foot">
                        <div>
                            <h4><i class="bi bi-film"></i> <span id="tourVideoTitle">Tour video</span></h4>
                            <p id="tourVideoDesc">A walkthrough of Talisay Beach Resort.</p>
                        </div>
                        <button type="button" class="vt-btn is-primary" onclick="switchViewMode('pano')">
                            <i class="bi bi-badge-vr"></i><span>Back to 360Â° view</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if($assets->isNotEmpty())
        <section class="vt-scenes" aria-labelledby="vtScenesTitle">
            <div class="vt-scenes-head">
                <h3 id="vtScenesTitle">Explore the resort <span>{{ $assets->count() }} {{ $assets->count() === 1 ? 'scene' : 'scenes' }}</span></h3>
                <p>Pick a scene below or tap a pin on the map. Drag the picture to look around.</p>
            </div>

            <div class="vt-scene-grid">
                @foreach($assets as $idx => $asset)
                @php $kind = $asset->type === 'video' ? 'video' : ($asset->isPanorama() ? 'pano' : 'photo'); @endphp
                <button type="button" id="sceneCard_{{ $asset->id }}" class="vt-scene scene-card" onclick="loadPanoramaScene('scene_{{ $asset->id }}', true)" aria-label="Open {{ $asset->title }}">
                    <span class="vt-scene-media">
                        @if($kind === 'video' && $asset->mediaUrl())
                            <video src="{{ $asset->mediaUrl() }}#t=1" muted playsinline preload="metadata" aria-hidden="true"></video>
                            <i class="bi bi-play-circle-fill"></i>
                        @elseif($asset->mediaUrl())
                            <img src="{{ $asset->mediaUrl() }}" alt="" loading="lazy">
                        @else
                            <i class="bi {{ $metaFor($asset, $idx)['icon'] }}"></i>
                        @endif
                        <span class="vt-scene-num">{{ $loop->iteration }}</span>
                        <span class="vt-scene-now"><i class="bi bi-eye-fill"></i> Viewing</span>
                        <span class="vt-scene-kind">
                            <i class="bi bi-{{ $kind === 'video' ? 'play-fill' : ($kind === 'pano' ? 'badge-vr' : 'image') }}"></i>
                            {{ $kind === 'video' ? 'Video' : ($kind === 'pano' ? '360Â°' : 'Photo') }}
                        </span>
                    </span>
                    <span class="vt-scene-body">
                        <strong>{{ $asset->title }}</strong>
                        @if(filled($asset->description))<span>{{ $asset->description }}</span>@endif
                    </span>
                </button>
                @endforeach
            </div>
        </section>
        @endif
    </main>
@endsection

@push('scripts')
<script>

        const tourScenes = {};
        const flatScenes = {};
        const videoScenes = {};
        const fallbackTourVideo = @json(is_file(public_path('videos/virtual-tour.mp4')) ? '/videos/virtual-tour.mp4' : '');
        const sceneLocations = {
            @foreach($assets as $asset)
            @php $meta = $metaFor($asset, $loop->index); @endphp
            'scene_{{ $asset->id }}': {
                id: {{ $asset->id }},
                key: 'scene_{{ $asset->id }}',
                index: {{ $loop->iteration }},
                title: {{ Js::from($asset->title) }},
                description: {{ Js::from((string) $asset->description) }},
                lat: {{ $meta['lat'] }},
                lng: {{ $meta['lng'] }},
                icon: "{{ $meta['icon'] }}"
            },
            @endforeach
        };

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
        const sceneSlugs = { 1: 'main-entrance', 2: 'cottage-area', 3: 'restaurant', 4: 'cabin-suite', 5: 'snorkeling' };

        @foreach($assets as $asset)
        @if($asset->type === 'video' && $asset->mediaUrl())
        videoScenes['scene_{{ $asset->id }}'] = {
            url: @json($asset->mediaUrl()),
            title: {{ Js::from($asset->title) }},
            description: {{ Js::from((string) $asset->description) }}
        };
        @elseif($asset->isPanorama())
        tourScenes['scene_{{ $asset->id }}'] = {
            title: {{ Js::from($asset->title) }},
            type: "equirectangular",
            panorama: @json($asset->mediaUrl()),
            autoLoad: true,
            autoRotate: -1.5,
            compass: true,
            hotspots: [
                @foreach(($asset->hotspots ?? []) as $hotspot)
                @php
                    $rawTarget = (string) ($hotspot['sceneId'] ?? '1');
                    $targetScene = match ($rawTarget) {
                        'cottage-area' => 'scene_2',
                        'restaurant' => 'scene_3',
                        'main-entrance' => 'scene_1',
                        default => 'scene_' . $rawTarget,
                    };
                @endphp
                {
                    pitch: {{ (float) ($hotspot['pitch'] ?? 0) }},
                    yaw: {{ (float) ($hotspot['yaw'] ?? 0) }},
                    type: "{{ ($hotspot['type'] ?? 'info') === 'scene' ? 'scene' : 'info' }}",
                    text: {{ Js::from($hotspot['text'] ?? '') }},
                    @if(($hotspot['type'] ?? 'info') === 'scene')
                    sceneId: {{ Js::from($targetScene) }},
                    @endif
                },
                @endforeach
            ]
        };
        if (sceneSlugs[{{ $asset->id }}]) tourScenes[sceneSlugs[{{ $asset->id }}]] = tourScenes['scene_{{ $asset->id }}'];
        @else
        flatScenes['scene_{{ $asset->id }}'] = @json($asset->mediaUrl());
        @endif
        @endforeach

        let viewer = null;
        let currentSceneKey = 'scene_{{ $openingScene->id ?? 1 }}';
        let isAutoRotating = true;
        let activeViewMode = 'pano';

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
        }

        @if($openingScene && $openingScene->isPanorama())
        try {
            viewer = pannellum.viewer('panoramaViewer', {
                default: {
                    firstScene: currentSceneKey,
                    sceneFadeDuration: 1000,
                    autoLoad: true,
                    autoRotate: -1.5,
                    showControls: false
                },
                scenes: tourScenes
            });

            viewer.on('load', function () {
                if (flatScenes[currentSceneKey]) return;
                const activeId = viewer.getScene();
                syncActiveSceneUI(slugToSceneId[activeId] || activeId);
            });
        } catch (e) {
            console.error('Pannellum initialization error:', e);
        }
        @endif

        const resortCenter = [10.5807, 124.7656];
        let mainMap = null;
        let miniMap = null;
        let mapMarkers = {};
        let miniMapMarkers = {};
        let satelliteTileLayer = null;
        let streetTileLayer = null;

        function initLeafletMaps() {
            satelliteTileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri',
                maxZoom: 19
            });
            streetTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            });

            mainMap = L.map('leafletMap', {
                center: resortCenter,
                zoom: 17,
                layers: [satelliteTileLayer],
                zoomControl: true
            });

            const miniSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 });
            miniMap = L.map('miniMapLeaflet', {
                center: resortCenter,
                zoom: 16,
                layers: [miniSatellite],
                zoomControl: false,
                attributionControl: false
            });

            Object.keys(sceneLocations).forEach((key) => {
                const loc = sceneLocations[key];

                const pinIcon = L.divIcon({
                    className: 'custom-map-icon',
                    html: `<div id="pin_${key}" class="radar-pin ${key === currentSceneKey ? 'active' : ''}" onclick="loadPanoramaScene('${key}', true)"><i class="bi ${loc.icon}"></i></div>`,
                    iconSize: [36, 36],
                    iconAnchor: [18, 18]
                });
                const marker = L.marker([loc.lat, loc.lng], { icon: pinIcon, title: loc.title }).addTo(mainMap);
                marker.bindPopup(`
                    <div class="vt-pop">
                        <small>Scene ${loc.index}</small>
                        <strong>${escapeHtml(loc.title)}</strong>
                        <p>${escapeHtml(loc.description)}</p>
                        <button type="button" onclick="loadPanoramaScene('${key}', true); if (!videoScenes['${key}']) switchViewMode('pano');">
                            <i class="bi bi-badge-vr"></i> Open this scene
                        </button>
                    </div>
                `);
                mapMarkers[key] = marker;

                const miniIcon = L.divIcon({
                    className: 'mini-map-icon',
                    html: `<div id="minipin_${key}" class="radar-pin ${key === currentSceneKey ? 'active' : ''}" style="width:24px;height:24px;font-size:11px;" onclick="loadPanoramaScene('${key}', true)"><i class="bi ${loc.icon}"></i></div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });
                miniMapMarkers[key] = L.marker([loc.lat, loc.lng], { icon: miniIcon, title: loc.title }).addTo(miniMap);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            initLeafletMaps();
            if (flatScenes[currentSceneKey]) showSceneMedia(currentSceneKey);
            syncActiveSceneUI(currentSceneKey);
        });

        function loadPanoramaScene(sceneKey, panMap = true) {
            const resolvedKey = slugToSceneId[sceneKey] || sceneKey;
            currentSceneKey = resolvedKey;
            showSceneMedia(resolvedKey);
            syncActiveSceneUI(resolvedKey, panMap);
        }

        function firstTourVideoKey() {
            const keys = Object.keys(videoScenes);
            return keys.length ? keys[0] : null;
        }

        function playTourVideo(sceneKey) {
            const player = document.getElementById('resortTourVideo');
            const empty = document.getElementById('tourVideoEmpty');
            const title = document.getElementById('tourVideoTitle');
            const desc = document.getElementById('tourVideoDesc');
            const clip = sceneKey ? videoScenes[sceneKey] : null;
            const url = clip ? clip.url : fallbackTourVideo;
            if (!player) return false;
            if (!url) {
                player.classList.add('hidden');
                if (empty) empty.classList.remove('hidden');
                return false;
            }
            player.classList.remove('hidden');
            if (empty) empty.classList.add('hidden');
            if (title) title.textContent = clip ? clip.title : 'Talisay Beach Resort walkthrough';
            if (desc) desc.textContent = clip && clip.description ? clip.description : 'A walkthrough of Talisay Beach Resort.';
            if (player.getAttribute('src') !== url) {
                player.pause();
                player.src = url;
                player.load();
            }
            return true;
        }

        function showSceneMedia(sceneKey) {
            const flatImage = document.getElementById('flatSceneImage');
            const pano = document.getElementById('panoramaViewer');
            if (videoScenes[sceneKey]) {
                if (flatImage) flatImage.classList.add('hidden');
                playTourVideo(sceneKey);
                if (activeViewMode !== 'video') {
                    switchViewMode('video');
                } else {
                    const player = document.getElementById('resortTourVideo');
                    if (player) player.play().catch(function () {});
                }
                return;
            }
            if (activeViewMode === 'video') {
                switchViewMode('pano');
            }
            if (flatScenes[sceneKey]) {
                if (flatImage) {
                    flatImage.src = flatScenes[sceneKey];
                    flatImage.alt = sceneLocations[sceneKey] ? sceneLocations[sceneKey].title : '';
                    flatImage.classList.remove('hidden');
                }
                if (pano) pano.style.visibility = 'hidden';
                return;
            }
            if (flatImage) flatImage.classList.add('hidden');
            if (pano) pano.style.visibility = 'visible';
            if (viewer && tourScenes[sceneKey] && viewer.getScene() !== sceneKey) {
                viewer.loadScene(sceneKey);
            }
        }

        function syncActiveSceneUI(sceneKey, panMap = true) {
            const loc = sceneLocations[sceneKey];
            if (!loc) return;

            const titleEl = document.getElementById('sceneInfoTitle');
            const descEl = document.getElementById('sceneInfoDesc');
            const indexEl = document.getElementById('sceneInfoIndex');
            if (titleEl) titleEl.textContent = loc.title;
            if (descEl) descEl.textContent = loc.description;
            if (indexEl) indexEl.textContent = loc.index;

            document.querySelectorAll('.scene-card').forEach(card => {
                card.classList.remove('is-active');
                card.removeAttribute('aria-current');
            });
            const activeCard = document.getElementById(`sceneCard_${loc.id}`);
            if (activeCard) {
                activeCard.classList.add('is-active');
                activeCard.setAttribute('aria-current', 'true');
            }

            document.querySelectorAll('.radar-pin').forEach(pin => pin.classList.remove('active'));
            const activePin = document.getElementById(`pin_${sceneKey}`);
            if (activePin) activePin.classList.add('active');
            const activeMiniPin = document.getElementById(`minipin_${sceneKey}`);
            if (activeMiniPin) activeMiniPin.classList.add('active');

            if (panMap && mainMap && mapMarkers[sceneKey]) {
                mainMap.panTo([loc.lat, loc.lng], { animate: true, duration: 1.0 });
            }
            if (miniMap) {
                miniMap.panTo([loc.lat, loc.lng], { animate: true });
            }
        }

        function resizeTourSurfaces(after) {
            const run = function () {
                if (viewer && typeof viewer.resize === 'function') viewer.resize();
                if (mainMap) mainMap.invalidateSize();
                if (miniMap) miniMap.invalidateSize();
                if (typeof after === 'function') after();
            };
            requestAnimationFrame(function () {
                run();
                setTimeout(run, 180);
            });
        }

        function switchViewMode(mode) {
            activeViewMode = mode;
            const viewport = document.getElementById('tourViewport');
            const panoCol = document.getElementById('panoramaCol');
            const mapCol = document.getElementById('mapCol');
            const videoCol = document.getElementById('videoCol');
            const miniMapWidget = document.getElementById('miniMapWidget');
            const videoPlayer = document.getElementById('resortTourVideo');
            const tabs = { pano: 'tabBtnPano', split: 'tabBtnSplit', map: 'tabBtnMap', video: 'tabBtnVideo' };

            Object.entries(tabs).forEach(([key, id]) => {
                const tab = document.getElementById(id);
                tab.classList.toggle('is-active', key === mode);
                tab.setAttribute('aria-pressed', key === mode ? 'true' : 'false');
            });

            if (mode !== 'video' && videoPlayer) {
                videoPlayer.pause();
            }

            viewport.classList.toggle('split-active', mode === 'split');
            panoCol.classList.toggle('hidden', mode === 'map' || mode === 'video');
            mapCol.classList.toggle('hidden', mode === 'pano' || mode === 'video');
            videoCol.classList.toggle('hidden', mode !== 'video');
            if (miniMapWidget) miniMapWidget.classList.toggle('hidden', mode !== 'pano');

            if (mode === 'map') {
                resizeTourSurfaces(function () {
                    if (mainMap) focusResortCenter();
                });
            } else if (mode === 'video') {
                const videoKey = videoScenes[currentSceneKey] ? currentSceneKey : firstTourVideoKey();
                if (videoKey) {
                    currentSceneKey = videoKey;
                    syncActiveSceneUI(videoKey, false);
                }
                if (playTourVideo(videoKey) && videoPlayer) {
                    videoPlayer.play().catch(function () {});
                }
            } else {
                resizeTourSurfaces();
            }
        }

        function setMapLayer(layerType) {
            const satellite = layerType === 'satellite';
            mainMap.removeLayer(satellite ? streetTileLayer : satelliteTileLayer);
            mainMap.addLayer(satellite ? satelliteTileLayer : streetTileLayer);
            document.getElementById('layerBtnSatellite').classList.toggle('is-active', satellite);
            document.getElementById('layerBtnStreets').classList.toggle('is-active', !satellite);
        }

        function focusResortCenter() {
            if (mainMap) {
                mainMap.flyTo(resortCenter, 17, { duration: 1.2 });
            }
        }

        function toggleMiniMapCollapse() {
            const widget = document.getElementById('miniMapWidget');
            const icon = document.getElementById('miniMapToggleIcon');
            const collapsed = widget.classList.toggle('minimized');
            icon.className = collapsed ? 'bi bi-plus' : 'bi bi-dash';
            if (!collapsed) {
                setTimeout(() => { if (miniMap) miniMap.invalidateSize(); }, 300);
            }
        }

        function toggleAutoRotate() {
            if (!viewer) return;
            isAutoRotating = !isAutoRotating;
            viewer.setAutoRotate(isAutoRotating ? -1.5 : false);
            const icon = document.getElementById('iconAutoRotate');
            if (icon) icon.className = isAutoRotating ? 'bi bi-pause-fill' : 'bi bi-play-fill';
        }

        function togglePanoFullscreen() {
            const elem = document.getElementById('tourViewport');
            if (!document.fullscreenElement) {
                elem.requestFullscreen().catch(err => alert(`Full screen isn't available: ${err.message}`));
            } else {
                document.exitFullscreen();
            }
        }
    
</script>
@endpush
