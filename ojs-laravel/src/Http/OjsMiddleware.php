<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Http;

use Closure;
use Illuminate\Http\Request;
use OpenJobSpec\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP middleware that injects the OJS client into the request attributes.
 */
class OjsMiddleware
{
    public function __construct(private readonly Client $client)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('ojs', $this->client);
        return $next($request);
    }
}
