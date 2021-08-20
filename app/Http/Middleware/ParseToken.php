<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use App\Exceptions\BaseException;
use App\Helpers\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\UserController;

class ParseToken
{
    public function handle(Request $req, Closure $next)
    {
        $res = null;
        if ($req->hasHeader('x-token')) {
            try {
                // access token
                $aToken = (new Parser())->parse(Crypt::decrypt($req->header('x-token')));
                if (!$aToken->verify(new Sha256(), env('X_TOKEN_KEY'))) {
                    // invalid access token
                    throw new BaseException('Invalid access token', 401, 401);
                }

                // set id and role in request
                Auth::setUser($aToken->getClaim('role'), $aToken->getClaim('id'));


                // access token expired
                // check refresh token and generate new tokens
                if (!$aToken->validate(new ValidationData())) {
                    // refresh token
                    $rToken = (new Parser())->parse(Crypt::decrypt($req->header('x-refresh-token')));
                    $rIsVerif = UserController::verifyRefreshToken($rToken);

                    // invalid refresh token
                    if (!$rIsVerif) throw new BaseException('Session expired. Please login again!', 401, 401);

                    // refresh token expired
                    if (!$rToken->validate(new ValidationData())) {
                        throw new BaseException('Session expired. Please login again!', 401, 401);
                    }

                    // generate new tokens with refresh token
                    $tokens = UserController::getNewTokens($rToken->getClaim('id'));
                    if ($tokens == null) throw new \Exception();

                    $aToken = (string) $tokens['aToken'];
                    $rToken = (string) $tokens['rToken'];

                    $res = $next($req);

                    // attach new tokens to response header
                    $res->header('x-token', $aToken);
                    $res->header('x-refresh-token', $rToken);
                }
            } catch (BaseException $e) {
                throw $e;
            } catch (\Throwable $th) {
                throw new BaseException($th->getMessage(), 401, 401);
            }
        }
        return $res != null ? $res : $next($req);
    }
}
