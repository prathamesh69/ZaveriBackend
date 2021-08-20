<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductBookmark extends Model
{
    protected $fillable = ['user_id', 'product_id'];
    protected $hidden = ['updated_at', 'image'];
    protected $appends = [];


    // Attributes
    //


    // Relationships
    //
}
