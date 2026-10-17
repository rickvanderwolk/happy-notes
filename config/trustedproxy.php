<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Set this when the app runs behind a reverse proxy that terminates HTTPS,
    | such as Caddy, Traefik or nginx in front of the Docker container. Use a
    | comma separated list of IP addresses, or '*' to trust every proxy.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
