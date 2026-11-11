@extends('layouts.public')

@section('title', 'Contact – '.$resortInfo['name'])
@section('meta_description', 'Contact '.$resortInfo['name'].' in '.$resortInfo['address'].' by phone or email.')

@section('content')
@php $mapQuery = urlencode($resortInfo['name'].', '.$resortInfo['address']); @endphp

<section class="lp-banner" style="background-image:url('/images/hero-landing.jpg')">
    <div class="lp-banner-box">
        <div class="lp-eyebrow">We are here to help</div>
        <h1>Contact Us</h1>
        <p>Questions about a stay, a group booking, or the resort itself? Call or email the front desk.</p>
    </div>
</section>

<section class="lp-section lp-reveal is-in">
    <div class="lp-contact-grid">
        <div>
            <div class="lp-label">Contact details</div>
            <h2 class="lp-title">Reach to us</h2>
            <p class="lp-copy">The front desk answers during opening hours. To reserve a room, a cottage, or the whole resort, use Booking.</p>
            <div class="lp-contact-card">
                <i class="bi bi-geo-alt-fill"></i>
                <div><strong>Address</strong><span>{{ $resortInfo['address'] }}</span>
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank" rel="noopener">Open in Google Maps <i class="bi bi-box-arrow-up-right"></i></a>
                </div>
            </div>
            @if($resortInfo['phone'])
                <div class="lp-contact-card">
                    <i class="bi bi-telephone-fill"></i>
                    <div><strong>Phone</strong><a href="tel:{{ preg_replace('/[^0-9+]/', '', $resortInfo['phone']) }}">{{ $resortInfo['phone'] }}</a></div>
                </div>
            @endif
            <div class="lp-contact-card">
                <i class="bi bi-envelope-fill"></i>
                <div><strong>Email</strong><a href="mailto:{{ $resortInfo['email'] }}">{{ $resortInfo['email'] }}</a></div>
            </div>
            <div class="lp-contact-card">
                <i class="bi bi-clock-fill"></i>
                <div><strong>Open daily</strong><span>{{ $resortInfo['opens'] }} – {{ $resortInfo['closes'] }}</span><span>Hourly stays start between 8:00 AM and 8:00 PM</span></div>
            </div>
            <div class="lp-contact-card">
                <i class="bi bi-wallet2"></i>
                <div><strong>Payments</strong><span>GCash, card, or cash on arrival. The GCash QR code is shown at checkout.</span></div>
            </div>
        </div>
    </div>
</section>

<section class="lp-section lp-reveal" style="padding-top:0;">
    <div class="lp-label">Find us</div>
    <h2 class="lp-title">How to get here</h2>
    <div class="lp-map">
        <iframe title="Map of {{ $resortInfo['name'] }}" src="https://www.google.com/maps?q={{ $mapQuery }}&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    </div>
    <div class="lp-info-grid">
        <div class="lp-info-item">
            <div class="lp-info-icon"><i class="bi bi-signpost-2"></i></div>
            <div><h4>By road</h4><p>The resort is in {{ $resortInfo['address'] }}. Tricycles and habal-habal from Baybay City proper know the way to Maslug beach.</p></div>
        </div>
        <div class="lp-info-item">
            <div class="lp-info-icon"><i class="bi bi-badge-vr"></i></div>
            <div><h4>See it first</h4><p>Not sure what to expect? <a href="{{ route('tour.viewer') }}" target="_blank" rel="noopener">Open the 360° tour</a> and walk the grounds from your phone.</p></div>
        </div>
    </div>
</section>

@endsection
