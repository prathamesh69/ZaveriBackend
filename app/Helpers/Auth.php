<?php

namespace App\Helpers;

use App\User;
use Illuminate\Http\Request;

class Auth
{
    protected static $user = null;

    public static function setUser($role, $userId)
    {
        Auth::$user = User::where('id', $userId)->where('role', $role)->first();
    }

    public static function user()
    {
        return Auth::$user;
    }

    public static function guest()
    {
        return Auth::$user == null;
    }

    public static function isAdmin()
    {
        if (Auth::guest()) return false;
        return Auth::user()->role === 'admin';
    }

    public static function isRetailer()
    {
        if (Auth::guest()) return false;
        return Auth::user()->role === 'retailer';
    }

    public static function isWholesaler()
    {
        if (Auth::guest()) return false;
        return Auth::user()->role === 'wholesaler';
    }

    public static function id()
    {
        if (Auth::guest()) return false;
        return Auth::user()->id;
    }

    public static function role()
    {
        if (Auth::guest()) return null;
        return Auth::user()->role;
    }
}
