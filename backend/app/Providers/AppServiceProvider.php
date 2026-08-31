<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->defineRateLimiters();
    }

    /**
     * The ceilings the `throttle:` middleware refers to by name.
     *
     * They have to be defined somewhere, and naming one that does not exist is worse than
     * having none at all: ThrottleRequests reads an unknown name as a number, gets zero, and
     * answers 429 to every request. Both names used in bootstrap/app.php and routes/api.php
     * are therefore declared here.
     */
    private function defineRateLimiters(): void
    {
        // Simulating is public and deliberately frictionless, so this is a ceiling on
        // automation rather than something a person filling in the wizard could reach.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        // Login and register: the two endpoints that decide who you are. Two limits at once,
        // because one alone leaves a way around it — the first stops guessing at a single
        // account, the second stops one address from spreading its guesses over many.
        //
        // The limiter runs before validation, so the email is whatever was posted and not
        // necessarily a string: casting an array to one raises a PHP warning, which Laravel
        // turns into a 500. Anything that is not a string shares a single bucket instead — it
        // is on its way to a 422 regardless.
        RateLimiter::for('auth', function (Request $request) {
            $email = $request->input('email');
            $account = Str::lower(is_string($email) ? $email : '');

            return [
                Limit::perMinute(5)->by($account.'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });
    }
}
