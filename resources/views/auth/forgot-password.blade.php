{{-- Extends the base layout designed for unauthenticated guest sessions --}}
@extends('layouts.guest')

{{-- Sets the browser tab title specifically for this password reset request page --}}
@section('title', 'Forgot Password - Talisay Smart Tourism')

{{-- Defines the main content area of the guest layout template --}}
@section('content')

{{-- Background wrapper div setting up full viewport height, flex layout, center alignment, relative positioning, and a premium ocean-themed gradient --}}
<div class="min-h-screen flex items-center justify-center relative overflow-hidden" style="background: linear-gradient(135deg, #0c4a6e 0%, #164e63 40%, #0ea5e9 100%);">
    
    {{-- Decorative background overlay with 20% opacity using a beach image from Unsplash to match the resort aesthetics --}}
    <div class="absolute inset-0 opacity-20" style="background-image: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920'); background-size: cover; background-position: center;"></div>
    
    {{-- Content container with relative positioning to sit above the background, max width constraints, and padding for mobile views --}}
    <div class="relative z-10 w-full max-w-md px-4">
        
        {{-- Card element containing the password reset request form, featuring white background with 95% opacity, backdrop blur, rounded corners, shadow, and inner padding --}}
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8">
            
            {{-- Header section containing the key icon, reset title, and instruction text --}}
            <div class="text-center mb-6">
                
                {{-- Decorative circular/square box for the logo with a smooth gradient background --}}
                <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-sky-500 to-teal-400 flex items-center justify-center mb-3">
                    
                    {{-- Bootstrap icon representing a key to symbolize password retrieval/security --}}
                    <i class="bi bi-key text-white text-2xl"></i>
                </div>
                
                {{-- Principal page heading for password retrieval --}}
                <h1 class="text-2xl font-bold">Forgot Password</h1>
                
                {{-- Secondary instruction text telling the user what to do --}}
                <p class="text-gray-500 text-sm">Enter your email to receive a reset link</p>
            </div>
            
            {{-- Form starting route submission to dispatch reset password emails --}}
            <form action="{{ route('password.email') }}" method="POST">
                
                {{-- Generates the CSRF token input field for secure post submissions --}}
                @csrf
                
                {{-- Form control group for email input --}}
                <div class="mb-4">
                    
                    {{-- Label indicating email address entry field --}}
                    <label class="form-label text-sm font-medium">Email Address</label>
                    
                    {{-- Text input element for email address entry, persisting old value if submission failed --}}
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                
                {{-- Submission button triggering the link dispatch --}}
                <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold py-3 rounded-lg transition">
                    
                    {{-- Graphic icon displayed inside submission button --}}
                    <i class="bi bi-envelope me-2"></i>Send Reset Link
                </button>
            </form>
            
            {{-- Navigation linkage back to login page --}}
            <div class="text-center mt-4">
                
                {{-- Link redirecting back to standard login screen --}}
                <a href="{{ route('login') }}" class="text-sky-600 text-sm hover:underline">Back to Login</a>
            </div>
        </div>
    </div>
</div>
@endsection
