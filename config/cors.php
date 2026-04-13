<?php

/**
 * Laravel CORS configuration.
 *
 * HandleCors (global middleware, Laravel 11) reads this file.
 * It handles the OPTIONS preflight before any route-group middleware runs,
 * so this is the only place that matters for preflight responses.
 *
 * Rules that apply here:
 * - supports_credentials must be true because the frontend sends
 *   credentials: "include" for the HttpOnly auth_token cookie.
 * - When supports_credentials is true, allowed_origins must list specific
 *   origins — wildcards are rejected by every browser per the CORS spec.
 * - FRONTEND_ALLOWED_ORIGINS is a comma-separated list, e.g.
 *     http://localhost:3000,https://pethiyan.com
 */

$rawOrigins = env('FRONTEND_ALLOWED_ORIGINS', 'http://localhost:3000,http://127.0.0.1:3000');

$allowedOrigins = array_values(
    array_filter(
        array_map('trim', explode(',', $rawOrigins))
    )
);

return [
    /*
     * Paths to which CORS headers are applied.
     * The pattern 'api/*' covers every route registered under /api.
     */
    'paths' => ['api/*'],

    /*
     * HTTP methods accepted from cross-origin requests.
     */
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
     * Explicit list of allowed origins. Must not be ['*'] when
     * supports_credentials is true.
     */
    'allowed_origins' => $allowedOrigins,

    /*
     * Regex patterns for origin matching (not needed here — using explicit list).
     */
    'allowed_origins_patterns' => [],

    /*
     * Headers the browser is allowed to send.
     * ['*'] is fine here — only the origin list needs to be explicit.
     */
    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With', 'Origin'],

    /*
     * Headers exposed to the browser JS.
     */
    'exposed_headers' => [],

    /*
     * Preflight cache duration in seconds (sent as Access-Control-Max-Age).
     * 7200 = 2 hours — reduces preflight round-trips.
     */
    'max_age' => 7200,

    /*
     * Must be true so the browser sends and receives the HttpOnly auth_token
     * cookie on cross-origin API requests.
     */
    'supports_credentials' => true,
];
