<?php

namespace App;

use App\Helpers\Auth;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $fillable = [
        'wholesaler_firm_id', 'category_id', 'brand_id', 'type_id',
        'melting', 'weight_from', 'weight_to', 'tags', 'mark', 'image',
    ];
    protected $hidden = ['image', 'updated_at'];
    protected $appends = [
        'image_url', 'thumb_url', 'share_link', 'category_name', 'subcategories', 'brand_name',
        'type_name', 'firm_name', 'firm', 'bookmarked', 'weight_range',
    ];


    // Helpers
    public function updateSubcategories($subcategory_ids)
    {
        $db_subcategory_ids = $this->subcategory()->pluck('id')->toArray();

        // deletable items
        $deletableItems = array_values(array_diff($db_subcategory_ids, $subcategory_ids));
        DB::table('product_subcategories')->whereIn('subcategory_id', $deletableItems)->delete();


        // new items
        $newItems = array_values(array_diff($subcategory_ids, $db_subcategory_ids));
        DB::table('product_subcategories')->insert(
            array_map(function ($sId) {
                return ['product_id' => $this->id, 'subcategory_id' => $sId];
            }, $newItems)
        );


        // update tags
        $deletableTags = Subcategory::whereIn('id', $deletableItems)->pluck('name')->toArray();
        $newTags = Subcategory::whereIn('id', $newItems)->pluck('name')->toArray();

        $tags = explode(',', $this->tags);
        $tags = array_diff($tags, $deletableTags);
        $tags = array_unique(array_merge($tags, $newTags));

        $tags = collect($tags)
            ->map(function ($tag) {
                return trim($tag);
            })
            ->reject(function ($tag) {
                return empty($tag);
            });

        $this->tags = $tags->join(',');
        $this->save();
    }

    // Attributes
    public function getImageUrlAttribute()
    {
        return ImageHelper::getImageUrl(ImageHelper::$TYPE_PRODUCT, $this->image);
    }

    public function getThumbUrlAttribute()
    {
        return ImageHelper::getThumbUrl(ImageHelper::$TYPE_PRODUCT, $this->image);
    }

    public function getCategoryNameAttribute()
    {
        return $this->category()->value('name');
    }

    public function getSubcategoriesAttribute()
    {
        return $this->subcategory()->select('id', 'name')->get();
    }

    public function getBrandNameAttribute()
    {
        return $this->brand()->value('name');
    }

    public function getTypeNameAttribute()
    {
        return $this->type()->value('name');
    }

    public function getFirmNameAttribute()
    {
        return $this->wholesalerFirm()->value('name');
    }

    public function getFirmAttribute()
    {
        return $this->wholesalerFirm()->selectRaw('name, mobile')->first();
    }

    public function getBookmarkedAttribute()
    {
        return Auth::isRetailer()
            ? $this->bookmark()->where('user_id', Auth::id())->exists()
            : false;
    }

    public function getShareLinkAttribute()
    {
        return env('APP_URL') . '/pi/' . $this->id;
    }

    public function getWeightRangeAttribute()
    {
        $range = $this->weight_from;
        if (!empty($this->weight_to) && $this->weight_to != $this->weight_from) {
            $range .= ' - ' . $this->weight_to;
        }
        return $range;
    }


    // Relationships
    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function subcategory()
    {
        return $this->belongsToMany('App\Subcategory', 'product_subcategories');
    }

    public function brand()
    {
        return $this->belongsTo('App\Brand');
    }

    public function type()
    {
        return $this->belongsTo('App\ProductType', 'type_id');
    }

    public function wholesalerFirm()
    {
        return $this->belongsTo('App\WholesalerFirm');
    }

    public function bookmark()
    {
        return $this->hasMany('App\ProductBookmark');
    }
}
