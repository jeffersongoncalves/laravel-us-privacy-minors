<?php

namespace JeffersonGoncalves\UsPrivacyMinors\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JeffersonGoncalves\UsPrivacyMinors\MinorsManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adult-only content: a reliable adult result on every request. Self-declaration does not count.
 */
class EnsureVerifiedAdult
{
    public function __construct(protected MinorsManager $children) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($this->children->isVerifiedAdult($user instanceof Model ? $user : null)) {
            return $next($request);
        }

        $route = config('us-privacy-minors.verify_route');

        if (is_string($route) && ! $request->expectsJson()) {
            return redirect()->guest(route($route));
        }

        abort(403, 'Age verification required.');
    }
}
