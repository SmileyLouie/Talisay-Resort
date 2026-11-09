<footer class="lp-footer">
    <div class="lp-footer-grid">
        <div>
            <div class="lp-footer-brand">
                <img src="{{ asset('images/logo.png') }}" alt="">
                <div>
                    <strong>{{ $resortInfo['name'] }}</strong>
                    <span>{{ __('Smart Tourism System') }}</span>
                </div>
            </div>
            <p>A beach resort in {{ $resortInfo['address'] }}. Book rooms and cottages by the night or by the hour, take the 360° tour, and manage your stay from this website or the Android app.</p>
        </div>
        <div>
            <h5>{{ __('Contact') }}</h5>
            <ul>
                <li><i class="bi bi-geo-alt"></i><span>{{ $resortInfo['address'] }}</span></li>
                @if($resortInfo['phone'])<li><i class="bi bi-telephone"></i><a href="tel:{{ preg_replace('/[^0-9+]/', '', $resortInfo['phone']) }}">{{ $resortInfo['phone'] }}</a></li>@endif
                <li><i class="bi bi-envelope"></i><a href="mailto:{{ $resortInfo['email'] }}">{{ $resortInfo['email'] }}</a></li>
                <li><i class="bi bi-clock"></i><span>{{ __('Open daily') }} {{ $resortInfo['opens'] }} – {{ $resortInfo['closes'] }}</span></li>
            </ul>
        </div>
        <div>
            <h5>{{ __('Explore') }}</h5>
            <ul>
                <li><i class="bi bi-info-circle"></i><a href="{{ route('public.about') }}">{{ __('About the resort') }}</a></li>
                <li><i class="bi bi-door-open"></i><a href="{{ route('public.rooms') }}">{{ __('Rooms') }}</a></li>
                <li><i class="bi bi-house-heart"></i><a href="{{ route('public.cottages') }}">{{ __('Cottages') }}</a></li>
                <li><i class="bi bi-water"></i><a href="{{ route('public.activities') }}">{{ __('Activities') }}</a></li>
                <li><i class="bi bi-calendar-check"></i><a href="{{ route('public.booking') }}">{{ __('Booking') }}</a></li>
                <li><i class="bi bi-badge-vr"></i><a href="{{ route('tour.viewer') }}">{{ __('360° virtual tour') }}</a></li>
                <li><i class="bi bi-chat-quote"></i><a href="{{ route('public.reviews') }}">{{ __('Guest reviews') }}</a></li>
                <li><i class="bi bi-envelope-paper"></i><a href="{{ route('public.contact') }}">{{ __('Contact us') }}</a></li>
            </ul>
        </div>
        <div>
            <h5>{{ __('Stay connected') }}</h5>
            <div class="lp-social">
                <a class="facebook" href="https://www.facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <a class="twitter" href="https://twitter.com" target="_blank" rel="noopener noreferrer" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
                <a class="youtube" href="https://www.youtube.com" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                <a class="instagram" href="https://www.instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
            </div>
        </div>
    </div>
    <div class="lp-footer-bottom">© {{ $resortInfo['name'] }}. Smart Tourism System.</div>
</footer>
