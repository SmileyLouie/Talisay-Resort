@extends('layouts.guest')

@section('title', 'Sign in - Talisay Beach Resort')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center px-3">
    <div class="bg-white shadow-sm rounded-4 p-4 text-center" style="max-width: 420px; width: 100%;">
        <img src="{{ asset('images/logo.png') }}" alt="Talisay Beach Resort" style="width: 64px; height: 64px; object-fit: contain;">
        <h1 class="h4 mt-3 mb-2">{{ $ok ? 'Signed in' : 'Sign-in needs attention' }}</h1>
        <p class="text-secondary mb-0">{{ $message }}</p>
    </div>
</div>
@endsection
