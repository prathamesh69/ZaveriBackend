<?php

namespace App\Helpers;

use App\Exceptions\BaseException;

class Utils
{
    public static function error($message, int $code)
    {
        throw new BaseException($message, $code);
    }

    public static function normalizeMobile($mobile)
    {
        if ($mobile == null) return null;

        $mobile = preg_replace("/[()\\-\\.\\s]{1,15}/", "", $mobile);
        if (strlen($mobile) > 10) $mobile = substr($mobile, -10);

        return $mobile;
    }
}
