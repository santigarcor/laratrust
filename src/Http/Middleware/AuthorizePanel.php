<?php

declare(strict_types=1);

namespace Laratrust\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Only lets in who the "viewLaratrust" gate allows. By default, that's anyone in the local environment only.
 *
 * @internal
 */
final class AuthorizePanel
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless(Gate::allows('viewLaratrust'), 403);

        return $next($request);
    }
}
