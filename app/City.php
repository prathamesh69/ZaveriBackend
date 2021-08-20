<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'state'];
    protected $hidden = ['updated_at'];
    protected $appends = ['display_name'];

    // Attributes
    public function getDisplayNameAttribute()
    {
        return $this->name . ', ' . $this->state;
    }

    // Relationships
    //
}
