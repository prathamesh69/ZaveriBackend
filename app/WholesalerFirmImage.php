<?php

namespace App;

use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Model;

class WholesalerFirmImage extends Model
{
    protected $fillable = ['wholesaler_firm_id'];
    protected $hidden = ['image', 'updated_at'];
    protected $appends = ['image_url', 'thumb_url'];
    protected $casts = [];


    // Attributes
    public function getImageUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_WHOLESALER_FIRM_IMAGE, $this->image);
    }

    public function getThumbUrlAttribute()
    {
        return ImageHelper::getThumbUrl(ImageHelper::$TYPE_WHOLESALER_FIRM_IMAGE, $this->image);
    }


    // Relationships
    //
}
