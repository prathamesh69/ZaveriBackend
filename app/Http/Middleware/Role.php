<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Exceptions\BaseException;
use App\Helpers\Auth;

class Role
{
    public function handle(Request $req, Closure $next, ...$roles)
    {
        if (!in_array(Auth::role(), $roles)) {
            throw new BaseException('Please login to continue.', 401, 401);
        }

        return $next($req);
    }
}
