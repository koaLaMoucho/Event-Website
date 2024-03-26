<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */

    protected function redirectTo(Request $request): ?string
    {
        $protectedRoutes = ['admin', 'my-tickets', 'profile', 'all-events'];

        if (in_array($request->route()->getName(), $protectedRoutes) && !$request->user()) {
            return route('all-events');
        }

        return null;
    }
}
