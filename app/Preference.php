<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    protected $fillable = ['value'];
    protected $hidden = ['created_at'];

    public static $PREF_WHOLESALER_APP = 'wholesaler_app';
    public static $PREF_RETAILER_APP = 'retailer_app';

    public static function wholesalerApp()
    {
        return json_decode(Preference::where('id', Preference::$PREF_WHOLESALER_APP)->value('value'));
    }

    public static function retailerApp()
    {
        return json_decode(Preference::where('id', Preference::$PREF_RETAILER_APP)->value('value'));
    }
}
