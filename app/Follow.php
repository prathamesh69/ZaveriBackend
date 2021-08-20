<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    protected $fillable = ['followed_id', 'follower_id'];
    protected $hidden = ['updated_at'];
    protected $appends = ['followed', 'follower'];
    protected $casts = ['accepted' => 'bool'];


    // Attributes
    public function getFollowedAttribute()
    {
        return $this->followed()->selectRaw('name, image, mobile')->first();
    }

    public function getFollowerAttribute()
    {
        return $this->follower()->selectRaw('name, image, mobile')->first();
    }



    // Relationships
    public function followed()
    {
        return $this->belongsTo('App\WholesalerFirm', 'followed_id');
    }

    public function follower()
    {
        return $this->belongsTo('App\User', 'follower_id')->where('role', 'retailer');
    }
}
