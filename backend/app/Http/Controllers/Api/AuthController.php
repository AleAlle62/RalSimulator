<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Session based, not token based: the SPA is same-origin, so the cookie Sanctum's stateful
 * middleware already issues is all authentication needs. Registering only creates an account —
 * simulating and reading a shared link never require one.
 */
class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        // Before the row is written, not after: this used to create the account and only then
        // throw on the missing session, answering 500 to a caller whose account now existed.
        $this->ensureSessionIsAvailable($request);

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $this->ensureSessionIsAvailable($request);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Credenziali non valide.',
            ]);
        }

        $request->session()->regenerate();

        return response()->json(['user' => Auth::user()]);
    }

    /**
     * Who the session belongs to. The SPA calls this once at start-up to pick up an existing
     * cookie; a 401 is the ordinary answer for a visitor who never signed in, not an error.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Unreachable today — auth:sanctum turns a session-less request away first, since this
        // app issues no bearer tokens — but the session call below is the same one, and the
        // guard costs nothing next to a 500 the day a token exists.
        $this->ensureSessionIsAvailable($request);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(status: 204);
    }

    /**
     * Refuse a request that has no session, before it is allowed to do anything.
     *
     * Sanctum attaches a session to an API request only when it looks like it came from the
     * frontend — a Referer or Origin matching a stateful domain. Anything else skips that
     * middleware, and with it CSRF: the request still reached these methods, and the
     * `$request->session()` call below then threw. On login that meant 500 for the right
     * password against 422 for the wrong one, which is a password oracle answering as fast as
     * it is asked. Hence the refusal comes first, before a credential is read or a row written.
     *
     * 419 rather than 401 deliberately: it is the same answer a missing CSRF token gets, so the
     * two are indistinguishable from outside.
     */
    private function ensureSessionIsAvailable(Request $request): void
    {
        abort_unless(
            $request->hasSession(),
            419,
            'Sessione non disponibile: la richiesta non proviene dal frontend.',
        );
    }
}
