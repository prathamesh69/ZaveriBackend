<?php

namespace App;

use App\Helpers\Auth;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RetailerRating extends Model
{
    protected $fillable = ['wholesaler_firm_id', 'rating', 'name', 'mobile', 'review', 'pincode', 'city_id', 'recommended'];
    protected $hidden = ['updated_at', 'image'];
    protected $appends = ['wholesaler_firm', 'thumb_url', 'image_url', 'can_modify', 'city'];


    // Attributes
    public function getWholesalerFirmAttribute()
    {
        return $this->wholesalerFirm()->selectRaw('name, image')->first();
    }

    public function getImageUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_RETAILER_RATING, $this->image);
    }

    public function getThumbUrlAttribute()
    {
        return ImageHelper::getThumbUrl(ImageHelper::$TYPE_RETAILER_RATING, $this->image);
    }

    public function getCanModifyAttribute()
    {
        return Auth::isWholesaler() && $this->wholesaler_firm_id == Auth::user()->wholesaler_firm_id;
    }

    public function getCityAttribute()
    {
        $city = $this->city()->first();
        if (empty($city)) return null;

        return $city->display_name;
    }

    public function getUserAttribute()
    {
        return $this->user()->first();
    }

    /** Get ratings overview for a mobile number */
    public static function getOverviewByMobile($mobile)
    {
        $rating = DB::table('retailer_ratings')->selectRaw(
            'COUNT(rating) AS count,
             IFNULL(ROUND( AVG(rating), 1 ), 0) AS average,
             COUNT( IF(rating = 5, 1, NULL) ) AS 5star,
             COUNT( IF(rating = 4, 1, NULL) ) AS 4star,
             COUNT( IF(rating = 3, 1, NULL) ) AS 3star,
             COUNT( IF(rating = 2, 1, NULL) ) AS 2star,
             COUNT( IF(rating = 1, 1, NULL) ) AS 1star'
        )->where('mobile', $mobile)->first();
        return $rating;
    }


    // Relationships
    public function wholesalerFirm()
    {
        return $this->belongsTo('App\WholesalerFirm', 'wholesaler_firm_id');
    }

    public function city()
    {
        return $this->belongsTo('App\City');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'mobile', 'mobile')->where('role', 'retailer');
    }
}
