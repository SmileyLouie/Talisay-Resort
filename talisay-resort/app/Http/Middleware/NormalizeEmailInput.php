<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Email addresses are stored in lowercase, so "Juan@Gmail.com" and "juan@gmail.com"
 * are the same account everywhere: sign-up, sign-in, password reset and admin forms.
 * A Staff ID typed in the sign-in email box has no "@" and is left as typed.
 */
class NormalizeEmailInput
{
    private const FIELDS = ['email', 'guest_email'];

    public function handle(Request $request, Closure $next): Response
    {
        foreach (self::FIELDS as $field) {
            $value = $request->input($field);
            if (is_string($value) && str_contains($value, '@')) {
                $request->merge([$field => mb_strtolower(trim($value))]);
            }
        }

        return $next($request);
    }
}
