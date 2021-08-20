<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductTag extends Model
{
    protected $fillable = ['tag', 'category_id'];
    protected $hidden = ['updated_at'];
}
