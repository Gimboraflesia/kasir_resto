<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantSetting extends Model
{
    protected $fillable = ['restaurant_name', 'logo_path', 'address', 'phone', 'receipt_note'];
}
