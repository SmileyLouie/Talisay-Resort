<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Talisay Smart Tourism')</title>

    {{-- Universal Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    @stack('styles')
    <style>
        a, a:hover { text-decoration: none; }
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --accent: #14b8a6;
            --sand: #f59e0b;
            --navy: #071e3d;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
    </style>
</head>
<body class="antialiased">
    @yield('content')

    {{-- Chatbot Widget for Login, Register & Guest Screens --}}
    @include('partials.guest-chatbot')

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @include('partials.required-stars')
    @stack('scripts')
</body>
</html>
