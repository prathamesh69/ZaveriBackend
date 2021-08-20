<?php

namespace App\Http\Controllers;

use App\City;
use App\WholesalerRating;
use App\Helpers\Auth;
use App\Helpers\ImageHelper;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Jobs\WRNotificationJob;
use App\User;
use Illuminate\Http\Request;

class WholesalerRatingController extends Controller
{
    /**
     * List ratings.
     * GET /api/wholesaler-ratings
     */
    public function index(Request $req)
    {
        if (Auth::isRetailer()) {
            $pincode = Auth::user()->pincode;
            $ratings = WholesalerRating::orderByRaw('retailer_id = ? DESC, pincode = ? DESC, pincode = ? DESC, pincode = ? DESC, created_at DESC', [Auth::id(), $pincode, $pincode + 1, $pincode - 1]);
        } else {
            $ratings = WholesalerRating::latest();
        }

        if ($query = $req->query('query', false)) {
            $ratings->whereRaw('(name LIKE ? OR mobile LIKE ?)', ["%$query%", "%$query%"]);
        }

        if ($retailer_id = $req->query('retailer_id', false)) {
            $ratings->where('retailer_id', $retailer_id);
        }

        if ($rating = $req->query('rating', false)) {
            $ratings->where('rating', $rating);
        }

        if ($city_id = $req->query('city_id', false)) {
            $ratings->whereIn('city_id', explode(',', $city_id));
        }

        if ($mobile = $req->query('mobile', false)) {
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
     * POST /api/wholesaler-ratings
     */
    public function create(Request $req)
    {
        if (Auth::isRetailer()) $req->merge(['retailer_id' => Auth::id()]);

        $req->merge(['mobile' => Utils::normalizeMobile($req->input('mobile'))]);

        $this->validate($req, [
            'retailer_id' => 'required|exists:users,id',
            'rating' => 'required|numeric|max:5|min:1',
            'name' => 'required|string',
            'mobile' => 'required|string',
            'review' => 'nullable|string',
            'image' => 'nullable|image',
            'recommended' => 'nullable|in:yes,no,none',
        ]);

        $duplicate = WholesalerRating::where('retailer_id', $req->input('retailer_id'))
            ->where('mobile', $req->input('mobile'))->first();

        if (!empty($duplicate)) Utils::error('You have already rated this wholesaler!', 400);

        $retailer = User::where('id', $req->input('retailer_id'))->select('pincode', 'city_id')->first();
        if (empty($retailer->pincode)) Utils::error('Please add pincode to your profile to be able to rate.', 400);

        // create rating
        $rating = new WholesalerRating;
        $rating->fill($req->all());
        $rating->pincode = $retailer->pincode;
        $rating->city_id = $retailer->city_id;
        if ($req->hasFile('image')) {
            $rating->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_WHOLESALER_RATING);
        }
        $rating->save();

        dispatch(new WRNotificationJob($rating, Auth::id()));

        return $rating;
    }

    /**
     * Show rating.
     * GET /api/wholesaler-ratings/{id}
     */
    public function show(Request $req, $id)
    {
        $rating = WholesalerRating::find($id);
        if (empty($rating)) Utils::error('Rating not found!', 404);
        return $rating;
    }

    /**
     * Show rating wholesaler.
     * GET /api/wholesaler-ratings/{id}/wholesaler
     */
    public function showWholesaler(Request $req, $id)
    {
        $rating = WholesalerRating::find($id);
        if (empty($rating)) Utils::error('Rating not found!', 404);

        $firm = $rating->firm;

        $wholesaler =  [
            "name" => $firm != null ? $firm->name : $rating->name,
            "mobile" => $rating->mobile,
            "whatsapp_mobile" => $firm != null ? $firm->mobile : $rating->mobile,
            "address" => null,
            "city" => $firm != null ? $firm->city : null,
            "pincode" => $firm != null ? $firm->pincode : null,
            "registered" => $firm != null,
            "follows_count" => $firm != null ? $firm->follow_count : 0,
            "rating" => WholesalerRating::getOverviewByMobile($rating->mobile),
            "created_at" => $firm != null ? $firm->created_at->toDateTimeString() : null,
            "image_url" => $firm != null && $firm->image_url && $rating->image_url == null ? $firm->image_url : $rating->image_url,
            "thumb_url" => $firm != null && $firm->thumb_url && $rating->thumb_url == null ? $firm->thumb_url : $rating->thumb_url,
        ];

        return $wholesaler;
    }

    /**
     * Update wholesaler rating.
     * POST /api/wholesaler-ratings/{id}
     */
    public function update(Request $req, $id)
    {
        $rating = WholesalerRating::find($id);
        if (empty($rating)) Utils::error('Rating not found!', 404);

        $this->validate($req, [
            'rating' => 'numeric|max:5|min:1',
            'name' => 'string',
            'mobile' => 'string',
            'review' => 'nullable|string',
            'image' => 'nullable|image',
            'recommended' => 'nullable|in:yes,no,none',
        ]);

        // update rating
        if ($req->has('rating')) $rating->rating = $req->input('rating');
        if ($req->has('name')) $rating->name = $req->input('name');
        if ($req->has('mobile')) $rating->mobile = $req->input('mobile');
        if ($req->has('review')) $rating->review = $req->input('review');
        if ($req->has('recommended')) $rating->recommended = $req->input('recommended');
        if ($req->hasFile('image')) {
            if ($rating->image != null) ImageHelper::delete(ImageHelper::$TYPE_WHOLESALER_RATING, $rating->image);
            $rating->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_WHOLESALER_RATING);
        }
        $rating->save();

        return $rating;
    }

    /**
     * Delete rating by id.
     *
     * DELETE /api/wholesaler-ratings/{id}
     */
    public function delete(Request $req, $id)
    {
        $rating = WholesalerRating::find($id);
        if (empty($rating)) return Utils::error('Rating not found!', 404);
        $rating->delete();
        if ($rating->image != null) ImageHelper::delete(ImageHelper::$TYPE_WHOLESALER_RATING, $rating->image);
        return $rating;
    }

    /**
     * List cities.
     * GET /api/wholesaler-ratings/cities
     */
    public function cities(Request $req)
    {
        $cities = City::whereIn('id', WholesalerRating::distinct()->pluck('city_id'))
            ->orderBy('name')->get();

        if ($cities->isEmpty()) Utils::error('No cities found!', 404);
        return $cities;
    }
}
