<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    public function redirect(Request $request, string $provider)
    {
        $this->assertProvider($provider);

        if (!$this->configured($provider)) {
            return $this->webFailure($request, $this->missingMessage($provider));
        }

        if ($request->query('client') === 'app' && $request->filled('attempt')) {
            $request->session()->put('social_attempt', (string) $request->query('attempt'));
        } else {
            $request->session()->forget('social_attempt');
        }

        $request->session()->put('social_from', $request->query('from') === 'register' ? 'register' : 'login');

        return $this->driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        $this->assertProvider($provider);

        try {
            $social = $this->driver($provider)->user();
            $user = $this->resolveTourist($provider, $social);
        } catch (Throwable $exception) {
            $message = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'Sign-in was cancelled or could not be completed. Try again.';

            return $this->fail($request, $message);
        }

        $attempt = $request->session()->pull('social_attempt');
        if (is_string($attempt) && $attempt !== '') {
            Cache::store('file')->put($this->cacheKey($attempt), [
                'status' => 'ok',
                'token' => $user->createToken('mobile')->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
            ], now()->addMinutes(10));

            return view('auth.social-return', [
                'ok' => true,
                'message' => 'You are signed in. Return to the Talisay Beach Resort app.',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function mobileStart(string $provider)
    {
        $this->assertProvider($provider);

        if (!$this->configured($provider)) {
            return response()->json([
                'message' => $this->missingMessage($provider),
            ], 422);
        }

        $attempt = (string) Str::uuid();
        Cache::store('file')->put($this->cacheKey($attempt), [
            'status' => 'pending',
        ], now()->addMinutes(10));

        return response()->json([
            'attempt' => $attempt,
            'url' => route('auth.social.redirect', [
                'provider' => $provider,
                'client' => 'app',
                'attempt' => $attempt,
            ]),
        ]);
    }

    public function mobileStatus(string $attempt)
    {
        $key = $this->cacheKey($attempt);
        $payload = Cache::store('file')->get($key);

        if (!is_array($payload)) {
            return response()->json(['status' => 'expired']);
        }

        if (($payload['status'] ?? null) === 'ok') {
            Cache::store('file')->forget($key);
        }

        return response()->json($payload);
    }

    private function driver(string $provider)
    {
        config([
            "services.$provider.redirect" => url("/auth/$provider/callback"),
        ]);

        return Socialite::driver($provider);
    }

    private function resolveTourist(string $provider, object $social): User
    {
        $column = $provider.'_id';
        $providerId = (string) $social->getId();
        $email = $social->getEmail();

        $user = User::where($column, $providerId)->first();
        if (!$user && is_string($email) && $email !== '') {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            if (!$user->isTourist()) {
                throw new \RuntimeException('This email belongs to a staff or admin account. Sign in on that portal with its password.');
            }

            if ($user->is_active === false || in_array($user->account_status, ['inactive', 'suspended'], true)) {
                throw new \RuntimeException('This guest account cannot sign in.');
            }

            $user->forceFill([
                $column => $providerId,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        }

        if (!is_string($email) || $email === '') {
            throw new \RuntimeException(ucfirst($provider).' did not share an email address. Allow email access and try again.');
        }

        $name = trim((string) ($social->getName() ?: Str::before($email, '@')));

        $user = User::create([
            'name' => Str::limit($name !== '' ? $name : 'Guest', 255, ''),
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'role' => 'tourist',
            'is_active' => true,
            'account_status' => 'active',
            $column => $providerId,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function fail(Request $request, string $message)
    {
        $attempt = $request->session()->pull('social_attempt');
        if (is_string($attempt) && $attempt !== '') {
            Cache::store('file')->put($this->cacheKey($attempt), [
                'status' => 'error',
                'message' => $message,
            ], now()->addMinutes(10));

            return view('auth.social-return', [
                'ok' => false,
                'message' => $message,
            ]);
        }

        return $this->webFailure($request, $message);
    }

    private function webFailure(Request $request, string $message)
    {
        $route = $request->query('from') === 'register' || $request->session()->pull('social_from') === 'register'
            ? 'register'
            : 'login';

        return redirect()->route($route)->withErrors(['email' => $message]);
    }

    private function configured(string $provider): bool
    {
        return filled(config("services.$provider.client_id"))
            && filled(config("services.$provider.client_secret"));
    }

    private function missingMessage(string $provider): string
    {
        $name = ucfirst($provider);

        return "Continue with $name is not set up yet. Add the $name client ID and secret on the server, then try again.";
    }

    private function assertProvider(string $provider): void
    {
        if (!in_array($provider, ['google', 'facebook'], true)) {
            abort(404);
        }
    }

    private function cacheKey(string $attempt): string
    {
        return 'social-auth:'.$attempt;
    }
}
