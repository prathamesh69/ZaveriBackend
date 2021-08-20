<?php

namespace App;

use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'image'];
    protected $hidden = ['image', 'updated_at'];
    protected $appends = ['image_url', 'thumb_url'];


    // Attributes
    public function getImageUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_CATEGORY, $this->image);
    }

    public function getThumbUrlAttribute()
    {
        return ImageHelper::getThumbUrl(ImageHelper::$TYPE_CATEGORY, $this->image);
    }
}
