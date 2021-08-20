<?php

namespace App;

use App\Helpers\Auth;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WholesalerRating extends Model
{
    protected $fillable = ['retailer_id', 'rating', 'name', 'mobile', 'review', 'pincode', 'city_id', 'recommended'];
    protected $hidden = ['updated_at', 'image'];
    protected $appends = ['retailer', 'thumb_url', 'image_url', 'can_modify', 'city'];


    /** Get ratings overview for a mobile number */
    public static function getOverviewByMobile($mobile)
    {
        $rating = DB::table('wholesaler_ratings')->selectRaw(
            'COUNT(rating) AS count,
             IFNULL(ROUND( AVG(rating), 1 ), 0) AS average,
             COUNT( IF(rating = 5, 1, NULL) ) AS 5star,
             COUNT( IF(rating = 4, 1, NULL) ) AS 4star,
             COUNT( IF(rating = 3, 1, NULL) ) AS 3star,
             COUNT( IF(rating = 2, 1, NULL) ) AS 2star,
             COUNT( IF(rating = 1, 1, NULL) ) AS 1star'
        )
            ->whereRaw('(mobile = ? OR (SELECT COUNT(*) FROM users WHERE mobile = ?) > 0)', [$mobile, $mobile])
            ->first();
        return $rating;
    }


    // Attributes
    public function getRetailerAttribute()
    {
        return $this->retailer()->selectRaw('name, image')->first();
    }

    public function getImageUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_WHOLESALER_RATING, $this->image);
    }

    public function getThumbUrlAttribute()
    {
        return ImageHelper::getThumbUrl(ImageHelper::$TYPE_WHOLESALER_RATING, $this->image);
    }

    public function getCanModifyAttribute()
    {
        return Auth::isRetailer() && $this->retailer_id == Auth::id();
    }

    public function getCityAttribute()
    {
        $city = $this->city()->first();
        if (empty($city)) return null;

        return $city->display_name;
    }

    public function getFirmAttribute()
    {
        return $this->firm()->first();
    }


    // Relationships
    public function retailer()
    {
        return $this->belongsTo('App\User', 'retailer_id');
    }

    public function city()
    {
        return $this->belongsTo('App\City');
    }

    public function firm()
    {
        return WholesalerFirm::whereRaw('(mobile = ? OR (SELECT COUNT(*) FROM users WHERE wholesaler_firm_id = wholesaler_firms.id AND mobile = ?) > 0)', [$this->mobile, $this->mobile]);
    }
}
