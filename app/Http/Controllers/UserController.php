<?php

namespace App\Http\Controllers;

use App\Helpers\Auth;
use App\Helpers\ImageHelper;
use App\Helpers\SmsHelper;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;
use Illuminate\Support\Facades\Crypt;

use App\User;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Jobs\NRNotificationJob;
use App\Jobs\NWNotificationJob;
use App\Notification;
use App\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List users.
     * GET /api/users
     */
    public function index(Request $req)
    {
        $users = User::where('role', '!=', 'admin')->latest();

        if ($query = $req->query('query', false)) {
            $users->whereRaw('(name LIKE ? OR username LIKE ? OR pincode LIKE ?)', ["%$query%", "%$query%", "%$query%"]);
        }

        if ($role = $req->query('role', false)) {
            $users->where('role', $role);
        }

        if ($city_id = $req->query('city_id', false)) {
            $users->where('city_id', $city_id);
        }

        if ($pincode = $req->query('pincode', false)) {
            $users->where('pincode', $pincode);
        }

        if (($approved = $req->query('approved', false)) !== false) {
            $users->where('approved', $approved);
        }

        if ($req->query('nopaginate', false) == '1') {
            $users = $users->get();
        } else {
            $users = $users->paginate($req->query('per_page', 20));
        }

        if ($users->isEmpty()) Utils::error('No users found!', 404);
        return $users;
    }

    /**
     * Create user.
     * POST /api/users
     */
    public function create(Request $req)
    {
        $this->validate($req, [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:10'],
            'username' => ['required', 'string', 'size:10', 'unique:users'],
            'role' => ['required', 'string', 'in:admin,wholesaler,retailer'],
            'password' => ['required', 'string', 'min:6'],
            'wholesaler_firm_id' => ['nullable', 'exists:wholesaler_firms,id'],
            'image' => 'nullable|image',
            'pincode' => 'nullable|string|size:6',
            'retailer_firm_name' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        // create user
        $user = new User;
        $user->fill($req->all());
        $user->password = Hash::make($req->input('password'));
        if ($req->hasFile('image')) {
            $user->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_USER);
        }
        $user->save();

        if ($user->role == 'wholesaler') dispatch(new NWNotificationJob($user));

        return $user;
    }
    
    public function create_retailer(Request $req)
    {
        $this->validate($req, [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:10'],
            'username' => ['required', 'string', 'size:10', 'unique:users'],
            'role' => ['required', 'string', 'in:admin,wholesaler,retailer'],
            'password' => ['required', 'string', 'min:6'],
            'retailer_firm_id' => ['nullable', 'exists:retailer_firms,id'],
            'image' => 'nullable|image',
            'pincode' => 'nullable|string|size:6',
            'retailer_firm_name' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        // create user
        $user = new User;
        $user->fill($req->all());
        $user->password = Hash::make($req->input('password'));
        if ($req->hasFile('image')) {
            $user->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_USER);
        }
        $user->save();

        if ($user->role == 'retailer') dispatch(new NWNotificationJob($user));

        return $user;
    }    
    
    /**
     * Show user.
     * GET /api/users/{id}
     */
    public function show(Request $req, $id)
    {
        if ($id == 'me') $user = Auth::user();
        else $user = User::find($id);

        if (empty($user)) Utils::error('User not found!', 404);
        return $user->append('firm')->append('app_preference');
    }

    /**
     * Update user.
     * POST /api/users/{id}
     */
    public function update(Request $req, $id)
    {
        if (Auth::isWholesaler() || Auth::isRetailer()) $id = Auth::id();

        $user = User::find($id);
        if (empty($user)) Utils::error('User not found!', 404);

        $this->validate($req, [
            'name' => ['string', 'max:255'],
            'mobile' => ['string', 'max:10'],
            'username' => ['string', 'size:10', Rule::unique('users')->ignore($user->id)],
            'password' => ['string', 'min:6'],
            'wholesaler_firm_id' => ['nullable', 'exists:wholesaler_firms,id'],
            'image' => 'nullable|image',
            'pincode' => 'nullable|string|size:6',
            'retailer_firm_name' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'extras' => 'nullable|json',
        ]);

        // update user
        if ($req->has('name')) $user->name = $req->input('name');
        if ($req->has('mobile')) $user->mobile = $req->input('mobile');
        if ($req->has('pincode')) $user->pincode = $req->input('pincode');
        if ($req->has('retailer_firm_name')) $user->retailer_firm_name = $req->input('retailer_firm_name');
        if ($req->has('city_id')) $user->city_id = $req->input('city_id');
        if ($req->has('extras')) $user->extras = $req->input('extras');
        if ($req->hasFile('image')) {
            if ($user->image != null) ImageHelper::delete(ImageHelper::$TYPE_USER, $user->image);
            $user->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_USER);
        }
        if (Auth::isAdmin()) {
            if ($req->has('wholesaler_firm_id')) $user->wholesaler_firm_id = $req->input('wholesaler_firm_id');
            if ($req->has('username')) $user->username = $req->input('username');
            if ($req->has('password')) $user->password = Hash::make($req->input('password'));
        }
        $user->save();

        $user->append('firm')->append('app_preference');

        return $user;
    }

    /**
     * Delete user by id.
     *
     * DELETE /api/users/{id}
     */
    public function delete(Request $req, $id)
    {
        $user = User::find($id);
        if (empty($user)) Utils::error('User not found!', 404);
        $user->delete();
        if ($user->image != null) ImageHelper::delete(ImageHelper::$TYPE_USER, $user->image);
        if ($user->visiting_card != null) ImageHelper::delete(ImageHelper::$TYPE_USER, $user->visiting_card);
        return $user;
    }

    /**
     * Mark approved.
     * POST /api/users/{id}/approved/{status}
     */
    public function updateApproved(Request $req, $id, $status)
    {
        if (!in_array($status, ['on', 'off'])) {
            Utils::error('Invalid status selected!', 400);
        }

        $user = User::find($id);
        if (empty($user)) Utils::error('User not found!', 404);

        // update user status
        $user->approved = $status == 'on';
        $user->save();

        if ($status == 'on') {
            Notification::createForToken(
                $user->fcm_token,
                "Account activated!",
                "Your Zaveri Bazaar account has been activated! Click to open the app and login."
            )->send();
        }

        return $user;
    }

    /**
     * List pincodes.
     * GET /api/users/pincodes
     */
    public function pincodes(Request $req)
    {
        $pincodes = User::distinct('pincode')->whereNotNull('pincode')->orderBy('pincode')->pluck('pincode');
        if ($pincodes->isEmpty()) Utils::error('No pincodes found!', 404);
        return $pincodes;
    }


    /**
     * Login user.
     * POST /api/users/login
     */
    public function login(Request $req)
    {
        $this->validate($req, [
            'role' => ['required', 'string', 'in:admin,wholesaler,retailer'],
            'username' => [
                'required', 'string', 'size:10',
                Rule::exists('users')->where(function ($query) use ($req) {
                    $query->where('role', $req->input('role'));
                })
            ],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'username.required' => "Please enter mobile number.",
            'username.exists' => "This mobile number is not registered with Zaveri Bazaar."
        ]);

        $user = User::where('username', $req->input('username'))->where('role', $req->input('role'))->first();

        if (!Hash::check($req->input('password'), $user->password)) {
            return Utils::error('Invalid password.', 400);
        } else if (!$user->approved) {
            return Utils::error('We are reviewing your account. Please login after 24 hours of signup.', 400);
        } else {
            $tokens = $this->getNewTokens($user->id);

            if ($req->has('fcm_token')) {
                $user->fcm_token = $req->input('fcm_token');
                $user->save();
            }

            $user->append('firm')->append('app_preference');

            return response()->json($user, 200, [
                'x-token' => (string) $tokens['aToken'],
                'x-refresh-token' => (string) $tokens['rToken'],
            ]);
        }
    }


    /**
     * Signup user.
     * POST /api/users/signup
     */
    public function signup(Request $req)
    {
        $this->validate($req, [
            'role' => ['required', 'string', 'in:retailer'],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:10'],
            'username' => ['required', 'string', 'size:10', 'unique:users',],
            'password' => ['required', 'string', 'min:6'],
            'pincode' => ['required', 'string', 'size:6'],
            'retailer_firm_name' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'exists:cities,id'],
            'visiting_card' => ['required', 'image'],
        ], [
            'username.unique' => 'Account with this number already exists.'
        ]);

        // create user
        $user = new User;
        $user->fill($req->all());
        $user->password = Hash::make($req->input('password'));
        $user->approved = false;
        if ($req->hasFile('visiting_card')) {
            $user->visiting_card = ImageHelper::saveImage($req->file('visiting_card'), ImageHelper::$TYPE_USER);
        }
        $user->save();

        if ($user->role == 'retailer') dispatch(new NRNotificationJob($user));

        $tokens = $this->getNewTokens($user->id);

        return response()->json($user, 200, [
            'x-token' => (string) $tokens['aToken'],
            'x-refresh-token' => (string) $tokens['rToken'],
        ]);
    }

    /**
     * Update user password.
     * POST /api/users/me/password
     */
    public function password(Request $req)
    {
        $user = Auth::user();
        if (empty($user)) Utils::error('User not found!', 404);

        $this->validate($req, [
            'password' => ['required', 'string', 'min:6'],
            'new_password' => ['required', 'string', 'min:6'],
        ]);


        if (!Hash::check($req->input('password'), $user->password)) {
            return Utils::error('Invalid password.', 400);
        } else {
            $user->password = Hash::make($req->input('new_password'));
            $user->save();

            $user->append('firm')->append('app_preference');

            $tokens = $this->getNewTokens($user->id);
            return response()->json($user, 200, [
                'x-token' => (string) $tokens['aToken'],
                'x-refresh-token' => (string) $tokens['rToken'],
            ]);
        }
    }

    /**
     * Reset user password.
     * POST /api/users/reset-password
     */
    public function resetPassword(Request $req)
    {
        $this->validate($req, [
            'role' => ['required', 'string', 'in:wholesaler,retailer'],
            'username' => [
                'required', 'string', 'size:10',
                Rule::exists('users')->where(function ($query) use ($req) {
                    $query->where('role', $req->input('role'));
                })
            ],
            'otp' => 'string|size:6',
            'password' => 'required_with:otp|string|min:6',
        ], [
            'username.required' => "Please enter mobile number.",
            'username.exists' => "This mobile number is not registered with Zaveri Bazaar."
        ]);

        $user = User::where('username', $req->input('username'))->first();
        if (empty($user)) Utils::error('User not found!', 404);

        if (!$req->has('otp')) {
            // send otp

            $otp = new Otp([
                'for' => 'password-reset',
                'mobile' => $user->username,
                'otp' => env('OTP_ENABLED') ? mt_rand(100000, 999999) : 123456,
            ]);

            $result = SmsHelper::sendOTP($user->username, 'Your OTP for Zaveri Bazaar account password reset is: ' . $otp->otp, $otp->otp);
            if ($result != 'success') Utils::error('Unable to send OTP! Please try again later.', 500);

            $otp->save();

            return [
                'message' => 'OTP Sent!',
                'username' => $user->username,
            ];
        } else {
            // verify otp 

            $otp = Otp::where('for', 'password-reset')->where('mobile', $user->username)->latest()->first();
            if (empty($otp)) Utils::error('Please send OTP first!', 400);

            if ($otp->otp != $req->input('otp')) Utils::error('Invalid OTP!', 400);

            $otp->delete();

            $user->password = Hash::make($req->input('password'));
            $user->save();

            $tokens = $this->getNewTokens($user->id);
            return response()->json($user, 200, [
                'x-token' => (string) $tokens['aToken'],
                'x-refresh-token' => (string) $tokens['rToken'],
            ]);
        }
    }


    /**
     * Token helper to get new tokens.
     */
    public static function getNewTokens($userId)
    {
        $user = User::find($userId);
        if (empty($user)) return null;

        $aToken = (new Builder())->setIssuedAt(time())->setExpiration(time() + (int) env('X_TOKEN_EXPIRY'))
            ->set('id', $user->id)->set('role', $user->role)
            ->sign((new Sha256()), env('X_TOKEN_KEY'))->getToken();

        $rToken = (new Builder())->setIssuedAt(time())->setExpiration(time() + (int) env('X_REFRESH_TOKEN_EXPIRY'))
            ->set('id', $user->id)->set('role', $user->role)
            ->sign((new Sha256()), env('X_REFRESH_TOKEN_KEY') . $user->password)->getToken();

        return array('aToken' => Crypt::encrypt((string) $aToken), 'rToken' => Crypt::encrypt((string) $rToken));
    }

    /**
     * Token helper to get verify tokens.
     */
    public static function verifyRefreshToken(Token $rToken)
    {
        $user = User::find($rToken->getClaim('id'));
        if (empty($user)) return false;
        return $rToken->verify(new Sha256(), env('X_REFRESH_TOKEN_KEY') . $user->password);
    }
}
