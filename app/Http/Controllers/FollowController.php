<?php

namespace App\Http\Controllers;

use App\Follow;
use App\Helpers\Auth;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\RetailerRating;
use App\User;
use App\WholesalerFirm;
use App\WholesalerRating;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FollowController extends Controller
{
    /**
     * List follows.
     * GET /api/follows
     */
    public function index(Request $req)
    {
        $follows = Follow::orderByRaw('accepted = 1 ASC, created_at DESC');

        if (Auth::isWholesaler()) {
            $follows->where('followed_id', Auth::user()->wholesaler_firm_id);
        } else if ($followed_id = $req->query('followed_id', false)) {
            $follows->where('followed_id', $followed_id);
        }

        if (Auth::isRetailer()) {
            $follows->where('follower_id', Auth::id());
        } else if ($follower_id = $req->query('follower_id', false)) {
            $follows->where('follower_id', $follower_id);
        }

        if ($accepted = $req->query('accepted', false)) {
            if ($accepted == '1') {
                $follows->where('accepted', true);
            } else {
                $follows->where('accepted', false);
            }
        }

        if ($req->query('nopaginate', false) == '1') {
            $follows = $follows->get();
        } else {
            $follows = $follows->paginate($req->query('per_page', 20));
        }

        if ($follows->isEmpty()) return Utils::error('No follows found!', 404);
        return $follows;
    }

    /**
     * Create follow.
     * POST /api/follows
     */
    public function create(Request $req)
    {
        if (Auth::isRetailer()) $req->merge(['follower_id' => Auth::id()]);
        if ($req->has('mobile')) {
            $mobile = $req->input('mobile');

            $firmId = User::where('role', 'wholesaler')
                ->where('mobile', $mobile)
                ->value('wholesaler_firm_id');

            if ($firmId == null) $firmId = WholesalerFirm::where('mobile', $mobile)->value('id');

            $req->merge(['followed_id' => $firmId]);
        }

        $this->validate($req, [
            'followed_id' => 'required|exists:wholesaler_firms,id',
            'follower_id' => ['required', Rule::exists('users', 'id')->where('role', 'retailer')],
        ]);

        // create follow
        $follow = new Follow;
        $follow->fill($req->all());

        $firm = WholesalerFirm::where('id', $follow->followed_id)->first();
        $follow->accepted = $firm->preferences['auto_accept_followers'] ?? false;

        $follow->save();

        return $follow;
    }

    /**
     * Show follow.
     * GET /api/follows/{id}
     */
    public function show(Request $req, $id)
    {
        $follow = Follow::find($id);
        if (empty($follow)) Utils::error('Follow not found!', 404);
        return $follow;
    }

    /**
     * Accept follow request.
     * POST /api/follows/{id}/accept
     */
    public function accept(Request $req, $id)
    {
        $follow = Follow::find($id);
        if (empty($follow)) Utils::error('Follow not found!', 404);

        $follow->accepted = true;
        $follow->save();

        return $follow;
    }

    /**
     * Delete follow by id.
     *
     * POST /api/follows/{id}/ignore
     * DELETE /api/follows/{id}
     */
    public function delete(Request $req, $id)
    {
        $follow = Follow::find($id);
        if (empty($follow)) return Utils::error('Follow not found!', 404);
        $follow->delete();
        return $follow;
    }

    /**
     * Show following retailer.
     * GET /api/follows/{id}/retailer
     */
    public function showRetailer(Request $req, $id)
    {
        $follow = Follow::find($id);
        if (empty($follow)) Utils::error('Follow not found!', 404);

        $user = $follow->follower()->first();
        $user->follows_count = $user->follow_count;
        $user->rating = RetailerRating::getOverviewByMobile($user->username);

        return $user;
    }

    /**
     * Show following wholesaler.
     * GET /api/follows/{id}/wholesaler
     */
    public function showWholesaler(Request $req, $id)
    {
        $follow = Follow::find($id);
        if (empty($follow)) Utils::error('Follow not found!', 404);

        $firm = $follow->followed()->first();
        $firm->follows_count = $firm->follow_count;
        $firm->rating = WholesalerRating::getOverviewByMobile($firm->mobile);

        return $firm;
    }
}
