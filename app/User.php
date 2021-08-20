<?php

namespace App;

use App\Helpers\Auth;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'name', 'mobile', 'username', 'role', 'wholesaler_firm_id', 'pincode', 'retailer_firm_name',
        'city_id', 'fcm_token', 'extras'
    ];
    protected $hidden = ['password', 'updated_at', 'image', 'visiting_card', 'fcm_token'];
    protected $appends = ['image_url', 'thumb_url', 'visiting_card_url', 'city'];


    // Attributes
    public function getFirmAttribute()
    {
        return $this->role == 'wholesaler' ? $this->wholesalerFirm()->first() : null;
    }

    public function getImageUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_USER, $this->image);
    }

    public function getThumbUrlAttribute()
    {
        return ImageHelper::getThumbUrl(ImageHelper::$TYPE_USER, $this->image);
    }

    public function getVisitingCardUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_USER, $this->visiting_card);
    }

    public function getAppPreferenceAttribute()
    {
        if (Auth::isRetailer() || $this->role == 'retailer') {
            return Preference::retailerApp();
        } else if (Auth::isWholesaler() || $this->role == 'wholesaler') {
            return Preference::wholesalerApp();
        } else {
            return null;
        }
    }

    public function getCityAttribute()
    {
        $city = $this->city()->first();
        if (empty($city)) return null;

        return $city->display_name;
    }

    public function getFollowCountAttribute()
    {
        if ($this->role == 'retailer') {
            return Follow::where('follower_id', $this->id)->count();
        }

        return 0;
    }

    public function getExtrasAttribute($value)
    {
        return json_decode($value);
    }


    // Relationships
    public function wholesalerFirm()
    {
        return $this->belongsTo('App\WholesalerFirm');
    }

    public function city()
    {
        return $this->belongsTo('App\City');
    }
}
