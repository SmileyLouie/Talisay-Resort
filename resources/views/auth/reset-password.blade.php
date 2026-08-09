{{-- Extends the base layout designed for unauthenticated guest sessions --}}
@extends('layouts.guest')

{{-- Sets the browser tab title specifically for this password reset submission page --}}
@section('title', 'Reset Password - Talisay Smart Tourism')

{{-- Defines the main content area of the guest layout template --}}
@section('content')

{{-- Background wrapper div setting up full viewport height, flex layout, center alignment, relative positioning, and a premium ocean-themed gradient --}}
<div class="min-h-screen flex items-center justify-center relative overflow-hidden" style="background: linear-gradient(135deg, #0c4a6e 0%, #164e63 40%, #0ea5e9 100%);">
    
    {{-- Decorative background overlay with 20% opacity using a beach image from Unsplash to match the resort aesthetics --}}
    <div class="absolute inset-0 opacity-20" style="background-image: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920'); background-size: cover; background-position: center;"></div>
    
    {{-- Content container with relative positioning to sit above the background, max width constraints, and padding for mobile views --}}
    <div class="relative z-10 w-full max-w-md px-4">
        
        {{-- Card element containing the password reset form, featuring white background with 95% opacity, backdrop blur, rounded corners, shadow, and inner padding --}}
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8">
            
            {{-- Header section containing reset password title --}}
            <div class="text-center mb-6">
                
                {{-- Principal page heading for password reset confirmation --}}
                <h1 class="text-2xl font-bold">Reset Password</h1>
            </div>
            
            {{-- Form starting route submission to update password --}}
            <form action="{{ route('password.update') }}" method="POST">
                
                {{-- Generates the CSRF token input field for secure post submissions --}}
                @csrf
                
                {{-- Hidden input storing the token retrieved from the reset link --}}
                <input type="hidden" name="token" value="{{ $request->token }}">
                
                {{-- Hidden input storing the email address retrieved from the reset link parameters --}}
                <input type="hidden" name="email" value="{{ $request->email }}">
                
                {{-- Form control group for new password field --}}
                <div class="mb-4">
                    
                    {{-- Label indicating password creation field --}}
                    <label class="form-label text-sm font-medium">New Password</label>
                    
                    {{-- Input element for entering the new password --}}
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                {{-- Form control group for password confirmation field --}}
                <div class="mb-4">
                    
                    {{-- Label indicating password confirmation field --}}
                    <label class="form-label text-sm font-medium">Confirm Password</label>
                    
                    {{-- Input element for verifying the new password entry --}}
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                
                {{-- Submission button triggering the update operation --}}
                <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold py-3 rounded-lg transition">Reset Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
