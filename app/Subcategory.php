<?php

namespace App;

use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['name', 'image', 'category_id'];
    protected $hidden = ['image', 'updated_at'];
    protected $appends = ['category_name', 'image_url', 'thumb_url'];


    // Attributes
    public function getCategoryNameAttribute()
    {
        return $this->category()->value('name');
    }

    public function getImageUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_SUBCATEGORY, $this->image);
    }

    public function getThumbUrlAttribute()
    {
        return ImageHelper::getThumbUrl(ImageHelper::$TYPE_SUBCATEGORY, $this->image);
    }


    // Relationships
    public function category()
    {
        return $this->belongsTo('App\Category');
    }
}
