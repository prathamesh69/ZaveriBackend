<?php

namespace App;

use App\Helpers\ImageHelper;
use App\Helpers\VideoHelper;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['wholesaler_firm_id', 'text', 'image', 'video'];
    protected $hidden = ['image', 'video', 'updated_at'];
    protected $appends = ['image_url', 'thumb_url', 'video_url', 'firm'];


    // Attributes
    public function getImageUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_POST, $this->image);
    }

    public function getThumbUrlAttribute()
    {
        return ImageHelper::getThumbUrl(ImageHelper::$TYPE_POST, $this->image);
    }

    public function getVideoUrlAttribute()
    {
        return VideoHelper::getUrl(VideoHelper::$TYPE_POST, $this->video);
    }

    public function getFirmAttribute()
    {
        return $this->firm()->selectRaw('name, mobile, image')->first();
    }


    // Relationships
    public function firm()
    {
        return $this->belongsTo('App\WholesalerFirm', 'wholesaler_firm_id');
    }
}
