<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = ['mobile', 'email', 'otp', 'for'];
    protected $hidden = ['otp', 'updated_at'];


    // Attributes
    //
}
