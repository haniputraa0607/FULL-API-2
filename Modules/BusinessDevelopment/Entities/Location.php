<?php

namespace Modules\BusinessDevelopment\Entities;

use App\Http\Models\City;
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
        'id_partner',
    ];
    public function location_partner(){
        return $this->belongsTo(Partner::class, 'id_partner');
    }
    public function location_city(){
        return $this->belongsTo(City::class, 'id_city');
    }

}
