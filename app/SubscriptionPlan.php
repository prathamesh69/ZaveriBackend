<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = ['role', 'title', 'description', 'validity', 'price'];
    protected $hidden = ['updated_at'];
    protected $appends = [];


    // Attributes
    //

    // Relationships
    //
}
