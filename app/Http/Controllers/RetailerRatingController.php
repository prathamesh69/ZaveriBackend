<?php

namespace App\Http\Controllers;

use App\City;
use App\RetailerRating;
use App\Helpers\Auth;
use App\Helpers\ImageHelper;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Jobs\RRNotificationJob;
use App\WholesalerFirm;
use Illuminate\Http\Request;

class RetailerRatingController extends Controller
{
    /**
     * List ratings.
     * GET /api/retailer-ratings
     */
    public function index(Request $req)
    {
        if (Auth::isWholesaler()) {
            $pincode = WholesalerFirm::where('id', Auth::user()->wholesaler_firm_id)->value('pincode');
            $ratings = RetailerRating::orderByRaw('wholesaler_firm_id = ? DESC, pincode = ? DESC, pincode = ? DESC, pincode = ? DESC, created_at DESC', [Auth::user()->wholesaler_firm_id, $pincode, $pincode + 1, $pincode - 1]);
        } else {
            $ratings = RetailerRating::latest();
        }

        if ($query = $req->query('query', false)) {
            $ratings->whereRaw('(name LIKE ? OR mobile LIKE ?)', ["%$query%", "%$query%"]);
        }

        if ($wholesaler_firm_id = $req->query('wholesaler_firm_id', false)) {
            $ratings->where('wholesaler_firm_id', $wholesaler_firm_id);
        }

        if ($rating = $req->query('rating', false)) {
            $ratings->where('rating', $rating);
        }

        if ($city_id = $req->query('city_id', false)) {
            $ratings->whereIn('city_id', explode(',', $city_id));
        }

        if (Auth::isRetailer()) {
            $ratings->where('mobile', Auth::user()->mobile);
        } else if ($mobile = $req->query('mobile', false)) {
            $ratings->where('mobile', $mobile);
        }

        if ($req->query('nopaginate', false) == '1') {
            $ratings = $ratings->get();
        } else {
            $ratings = $ratings->paginate($req->query('per_page', 20));
        }

        if ($ratings->isEmpty()) return Utils::error('No ratings found!', 404);
        return $ratings;
    }

    /**
     * Create rating.
     * POST /api/retailer-ratings
     */
    public function create(Request $req)
    {
        if (Auth::isWholesaler()) $req->merge(['wholesaler_firm_id' => Auth::user()->wholesaler_firm_id]);

        $req->merge(['mobile' => Utils::normalizeMobile($req->input('mobile'))]);

        $this->validate($req, [
            'wholesaler_firm_id' => 'required|exists:wholesaler_firms,id',
            'rating' => 'required|numeric|max:5|min:1',
            'name' => 'required|string',
            'mobile' => 'required|string',
            'review' => 'nullable|string',
            'image' => 'nullable|image',
            'recommended' => 'nullable|in:yes,no,none',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        $duplicate = RetailerRating::where('wholesaler_firm_id', $req->input('wholesaler_firm_id'))
            ->where('mobile', $req->input('mobile'))->first();

        if (!empty($duplicate)) Utils::error('You have already rated this retailer!', 400);

        $firm = WholesalerFirm::where('id', $req->input('wholesaler_firm_id'))->select('pincode', 'city_id')->first();
        if (empty($firm->pincode)) Utils::error('Please add pincode to your firm profile to be able to rate.', 400);

        // create rating
        $rating = new RetailerRating;
        $rating->fill($req->all());
        $rating->pincode = $firm->pincode;
        if (!$req->has('city_id')) $rating->city_id = $firm->city_id;
        if ($req->hasFile('image')) {
            $rating->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_RETAILER_RATING);
        }
        $rating->save();

        dispatch(new RRNotificationJob($rating));

        return $rating;
    }

    /**
     * Show rating.
     * GET /api/retailer-ratings/{id}
     */
    public function show(Request $req, $id)
    {
        $rating = RetailerRating::find($id);
        if (empty($rating)) Utils::error('Rating not found!', 404);
        return $rating;
    }

    /**
     * Show rating retailer.
     * GET /api/retailer-ratings/{id}/retailer
     */
    public function showRetailer(Request $req, $id)
    {
        $rating = RetailerRating::find($id);
        if (empty($rating)) Utils::error('Rating not found!', 404);

        $user = $rating->user;

        $retailer =  [
            "name" => $user != null ? $user->name : $rating->name,
            "mobile" => $user != null ? $user->mobile : $rating->mobile,
            "whatsapp_mobile" => $user != null ? $user->mobile : $rating->mobile,
            "address" => null,
            "city" => $user != null ? $user->city : $rating->city,
            "pincode" => $user != null ? $user->pincode : $rating->pincode,
            "registered" => $user != null,
            "follows_count" => $user != null ? $user->follow_count : 0,
            "rating" => RetailerRating::getOverviewByMobile($rating->mobile),
            "created_at" => $user != null ? $user->created_at->toDateTimeString() : null,
            "image_url" => $user != null && $user->image_url && $rating->image_url == null ? $user->image_url : $rating->image_url,
            "thumb_url" => $user != null && $user->thumb_url && $rating->thumb_url == null ? $user->thumb_url : $rating->thumb_url,
        ];

        return $retailer;
    }

    /**
     * Update retailer rating.
     * POST /api/retailer-ratings/{id}
     */
    public function update(Request $req, $id)
    {
        $rating = RetailerRating::find($id);
        if (empty($rating)) Utils::error('Rating not found!', 404);

        $this->validate($req, [
            'rating' => 'numeric|max:5|min:1',
            'name' => 'string',
            'mobile' => 'string',
            'review' => 'nullable|string',
            'image' => 'nullable|image',
            'recommended' => 'nullable|in:yes,no,none',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        // update rating
        if ($req->has('rating')) $rating->rating = $req->input('rating');
        if ($req->has('name')) $rating->name = $req->input('name');
        if ($req->has('mobile')) $rating->mobile = $req->input('mobile');
        if ($req->has('review')) $rating->review = $req->input('review');
        if ($req->has('recommended')) $rating->recommended = $req->input('recommended');
        if ($req->has('city_id')) $rating->city_id = $req->input('city_id');
        if ($req->hasFile('image')) {
            if ($rating->image != null) ImageHelper::delete(ImageHelper::$TYPE_RETAILER_RATING, $rating->image);
            $rating->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_RETAILER_RATING);
        }
        $rating->save();

        return $rating;
    }

    /**
     * Delete rating by id.
     *
     * DELETE /api/retailer-ratings/{id}
     */
    public function delete(Request $req, $id)
    {
        $rating = RetailerRating::find($id);
        if (empty($rating)) return Utils::error('Rating not found!', 404);
        $rating->delete();
        if ($rating->image != null) ImageHelper::delete(ImageHelper::$TYPE_RETAILER_RATING, $rating->image);
        return $rating;
    }

    /**
     * Ratings overview.
     *
     * GET /api/retailer-ratings/overview
     */
    public function overview(Request $req)
    {
        $rating = RetailerRating::selectRaw('rating, COUNT(rating) AS count')->groupBy('rating')->get();
        if (empty($rating)) return Utils::error('Rating not found!', 404);

        $rating =  $rating->mapWithKeys(function ($r) {
            return [$r->rating => $r->count];
        })->all();

        return [
            '1' => isset($rating['1']) ? $rating['1'] : 0,
            '2' => isset($rating['2']) ? $rating['2'] : 0,
            '3' => isset($rating['3']) ? $rating['3'] : 0,
            '4' => isset($rating['4']) ? $rating['4'] : 0,
            '5' => isset($rating['5']) ? $rating['5'] : 0,
        ];
    }

    /**
     * List cities.
     * GET /api/retailer-ratings/cities
     */
    public function cities(Request $req)
    {
        $cities = City::whereIn('id', RetailerRating::distinct()->pluck('city_id'))
            ->orderBy('name')->get();

        if ($cities->isEmpty()) Utils::error('No cities found!', 404);
        return $cities;
    }
}
