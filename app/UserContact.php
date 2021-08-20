<?php

namespace App;

use App\Helpers\Auth;
use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Model;

class UserContact extends Model
{
    protected $fillable = ['user_id', 'name', 'mobile', 'device_id'];
    protected $hidden = ['updated_at'];
    protected $appends = ['user_name', 'can_follow', 'can_invite', 'can_rate'];


    // Attributes
    public function getUserNameAttribute()
    {
        return $this->user()->value('name');
    }

    public function getCanFollowAttribute()
    {
        $firmId = null;
        $retailerId = null;

        if (Auth::isWholesaler()) {
            $firmId = User::where('role', 'wholesaler')->where('id', $this->user_id)->value('wholesaler_firm_id');
            $retailerId = User::where('role', 'retailer')->where('username', $this->mobile)->value('id');
        }
        // 
        else if (Auth::isRetailer()) {
            $retailerId = Auth::id();
            $firmId = User::where('role', 'wholesaler')->where('mobile', $this->mobile)->value('wholesaler_firm_id');
            if ($firmId == null) {
                $firmId = WholesalerFirm::where('mobile', $this->mobile)->value('id');
            }
        }

        if ($firmId == null || $retailerId == null) return false;
        return !Follow::where('followed_id', $firmId)->where('follower_id', $retailerId)->exists();
    }

    public function getCanInviteAttribute()
    {
        if (Auth::isWholesaler()) {
            return !User::where('role', 'retailer')->where('mobile', $this->mobile)->exists();
        } else if (Auth::isRetailer()) {
            return !(User::where('role', 'wholesaler')->where('mobile', $this->mobile)->exists()
                || WholesalerFirm::where('mobile', $this->mobile)->exists());
        }

        return false;
    }

    public function getCanRateAttribute()
    {
        if (Auth::isWholesaler()) {
            return !RetailerRating::where('wholesaler_firm_id', Auth::user()->wholesaler_firm_id)
                ->where('mobile', $this->mobile)->exists();
        } else if (Auth::isRetailer()) {
            return !WholesalerRating::where('retailer_id', Auth::id())
                ->where('mobile', $this->mobile)->exists();
        }

        return false;
    }


    // Relationships
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
