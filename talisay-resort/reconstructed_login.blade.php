        <div class="auth-form-inner">

            @php
                $currentPortal = old('portal', request('portal'));
            @endphp

            {{-- Brand & Header --}}
            <div class="auth-brand-row">
                <img src="{{ asset('logo.png') }}" class="auth-brand-logo" alt="Talisay Beach Resort Logo">
                <div>
                    <div class="auth-brand-name">Talisay Beach Resort</div>
                    <div class="auth-brand-sub">Smart Tourism System</div>
                </div>
            </div>

            @if($currentPortal === 'admin')
                <h2 class="auth-form-title">Admin Log In</h2>
                <p class="auth-form-subtitle">Authorized administrator access to management controls.</p>
            @elseif($currentPortal === 'client')
                <h2 class="auth-form-title">Log In</h2>
                <p class="auth-form-subtitle">Access your bookings, reviews, and front desk services.</p>
            @else
                <h2 class="auth-form-title">Log In</h2>
                <p class="auth-form-subtitle">Enter your account credentials to access the system.</p>
            @endif

            {{-- Error Alert --}}
            @if($errors->any())
            <div class="auth-alert">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0 text-rose-500"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            {{-- Success Flash Message --}}
            @if(session('status'))
            <div class="auth-alert auth-alert-success">
                <i class="bi bi-check-circle-fill flex-shrink-0 text-emerald-500"></i>
                <span>{{ session('status') }}</span>
            </div>
            @endif

            {{-- Login Form --}}
            <form id="loginForm" action="{{ route('login.post') }}" method="POST">
                @csrf
                <input type="hidden" name="portal" value="{{ $currentPortal }}">

                {{-- Email / Username Field --}}
                <div class="auth-field-group">
                    <label class="auth-field-label" for="emailInput">
                        Email Address or Username
                    </label>
                    <div class="auth-input-wrap">
                        <input
                            type="text"
                            name="email"
                            id="emailInput"
                            class="auth-field-input"
                            placeholder="Email address or username"
                            value="{{ old('email') }}"
                            required
                            autofocus
                        >
                    </div>
                </div>

                {{-- Password Field --}}
                <div class="auth-field-group">
                    <label class="auth-field-label" for="passwordField">
                        Password
                    </label>
                    <div class="auth-input-wrap password-wrap">
                        <input
                            type="password"
                            name="password"
                            id="passwordField"
                            class="auth-field-input"
                            placeholder="Password"
                            required
                        >
                        <button type="button" class="auth-pw-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                {{-- Remember Me & Forgot Password --}}
                <div class="auth-row-between">
                    <label class="auth-remember">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="auth-forgot">Forgot password?</a>
                </div>

                {{-- Solid Ocean Blue Pill Button --}}
                <button type="submit" class="auth-submit-pill">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Log In</span>
                </button>
            </form>



            {{-- Register Prompt (Only for Client/Tourist, hidden on Admin portal) --}}
            @if($currentPortal !== 'admin')
            <div class="auth-register-footer">
                <span>Don't have an account yet?</span>
                <a href="{{ route('register') }}" class="auth-register-pill">
                    <i class="bi bi-person-plus text-sky-600"></i>
                    <span>Register as Tourist</span>
                </a>
            </div>
            @endif

        </div>
    </div>

</div>

@push('scripts')
/* Register Footer */
.auth-register-footer {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.auth-register-footer span {
    font-size: 12.5px;
    color: #64748b;
}

/* White Pill Button with Subtle Border for Register */
.auth-register-pill {
    width: 100%;
    height: 42px;
    border-radius: 9999px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    color: #0f172a;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.auth-register-pill:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    color: #0f172a;
    transform: translateY(-1px);
}

/* Responsive Styles */
@media (max-width: 960px) {
    .auth-split-screen {
        flex-direction: column;
    }
    .auth-hero-panel {
        min-height: 280px;
        flex: none;
        padding: 24px;
    }
    .auth-hero-subtitle,
    .auth-hero-features {
        display: none;
    }
    .auth-hero-title {
        font-size: 1.8rem;
    }
    .auth-form-panel {
        flex: none;
        min-height: auto;
        padding: 36px 20px 48px;
        box-shadow: none;
    }
}
</style>
@endpush

@section('content')

        <div class="auth-form-inner">

            @php
                $currentPortal = old('portal', request('portal'));
            @endphp

            {{-- Brand & Header --}}
            <div class="auth-brand-row">
                <img src="{{ asset('logo.png') }}" class="auth-brand-logo" alt="Talisay Beach Resort Logo">
                <div>
                    <div class="auth-brand-name">Talisay Beach Resort</div>
                    <div class="auth-brand-sub">Smart Tourism System</div>
                </div>
            </div>

            @if($currentPortal === 'admin')
                <h2 class="auth-form-title">Admin Log In</h2>
                <p class="auth-form-subtitle">Authorized administrator access to management controls.</p>
            @elseif($currentPortal === 'client')
                <h2 class="auth-form-title">Log In</h2>
                <p class="auth-form-subtitle">Access your bookings, reviews, and front desk services.</p>
            @else
                <h2 class="auth-form-title">Log In</h2>
                <p class="auth-form-subtitle">Enter your account credentials to access the system.</p>
            @endif

            {{-- Error Alert --}}
            @if($errors->any())
            <div class="auth-alert">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0 text-rose-500"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            {{-- Success Flash Message --}}
            @if(session('status'))
            <div class="auth-alert auth-alert-success">
                <i class="bi bi-check-circle-fill flex-shrink-0 text-emerald-500"></i>
                <span>{{ session('status') }}</span>
            </div>
            @endif
            @endif

            {{-- Login Form --}}
            <form id="loginForm" action="{{ route('login.post') }}" method="POST">
                @csrf
                <input type="hidden" name="portal" value="{{ $currentPortal }}">

                {{-- Email / Username Field --}}
                <div class="auth-field-group">
                    <label class="auth-field-label" for="emailInput">
                        Email Address or Username
                    </label>
                    <div class="auth-input-wrap">
                        <input
                            type="text"
                            name="email"
                            id="emailInput"
                            class="auth-field-input"
                            placeholder="Email address or username"
                            value="{{ old('email') }}"
                            required
                            autofocus
                        >
                    </div>
                </div>

                {{-- Password Field --}}
                <div class="auth-field-group">
                    <label class="auth-field-label" for="passwordField">
                        Password
                    </label>
                    <div class="auth-input-wrap password-wrap">
                        <input
                            type="password"
                            name="password"
                            id="passwordField"
                            class="auth-field-input"
                            placeholder="Password"
                            required
                        >
                        <button type="button" class="auth-pw-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                {{-- Remember Me & Forgot Password --}}
                <div class="auth-row-between">
                    <label class="auth-remember">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="auth-forgot">Forgot password?</a>
                </div>

                {{-- Solid Ocean Blue Pill Button --}}
                <button type="submit" class="auth-submit-pill">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Log In</span>
                </button>
            </form>



            {{-- Register Prompt (Only for Client/Tourist, hidden on Admin portal) --}}
            @if($currentPortal !== 'admin')
            <div class="auth-register-footer">
                <span>Don't have an account yet?</span>
                <a href="{{ route('register') }}" class="auth-register-pill">
                    <i class="bi bi-person-plus text-sky-600"></i>
                    <span>Register as Tourist</span>
                </a>
            </div>
            @endif

        </div>
    </div>

</div>

@push('scripts')
<script>
function togglePassword() {
    const field = document.getElementById('passwordField');
    const icon  = document.getElementById('toggleIcon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
@endpush
@endsection

