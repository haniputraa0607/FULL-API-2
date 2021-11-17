<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistLocation extends Model
{
    protected $primaryKey = 'id_user_hair_stylist';
    public $timestamps = false;

    protected $fillable = [
        'id_user_hair_stylist',
        'latitude',
        'longitude'
    ];
}
