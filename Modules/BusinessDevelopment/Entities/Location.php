<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $primaryKey = 'id_location';
    protected $fillable = [ 
        'name', 
        'address', 
        'id_city', 
        'latitude', 
        'longitude',
        'pic_name',
        'pic_contact',
        'id_user_franchise',
    ];
}
