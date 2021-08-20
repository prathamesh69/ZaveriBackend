<?php

namespace App;

use App\Helpers\Auth;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Model;

class WholesalerFirm extends Model
{
    protected $fillable = [
        'name', 'mobile', 'image', 'address', 'gst', 'marks', 'meltings', 'pincode', 'city_id', 'preferences',
        'email_addresses', 'icom_numbers', 'landline_numbers', 'links',
    ];
    protected $hidden = ['image', 'updated_at'];
    protected $appends = ['image_url', 'thumb_url', 'wholesalers', 'qr_image_url', 'follow_id', 'city'];
    protected $casts = [];


    // Attributes
    public function getImageUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_WHOLESALER_FIRM, $this->image);
    }

    public function getThumbUrlAttribute()
    {
        return ImageHelper::getThumbUrl(ImageHelper::$TYPE_WHOLESALER_FIRM, $this->image);
    }

    public function getWholesalersAttribute()
    {
        return $this->wholesaler()->get();
    }

    public function getMarksAttribute($value)
    {
        return json_decode($value);
    }

    public function getMeltingsAttribute($value)
    {
        return json_decode($value);
    }

    public function getPreferencesAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getEmailAddressesAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getIcomNumbersAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getLandlineNumbersAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getLinksAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getQrImageUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_WHOLESALER_FIRM, $this->qr_code);
    }

    public function getFollowIdAttribute()
    {
        if (!Auth::isRetailer()) return false;

        return Follow::where('followed_id', $this->id)
            ->where('follower_id', Auth::id())->value('id');
    }

    public function getCityAttribute()
    {
        $city = $this->city()->first();
        if (empty($city)) return null;

        return $city->display_name;
    }

    public function getFollowCountAttribute()
    {
        return Follow::where('followed_id', $this->id)->count();
    }


    // Relationships
    public function wholesaler()
    {
        return $this->hasMany('App\User', 'wholesaler_firm_id')->where('role', 'wholesaler');
    }

    public function city()
    {
        return $this->belongsTo('App\City');
    }
}
