<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletStarterBundling extends Model
{
    public $primaryKey = 'id_outlet_starter_bundling';
    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
    ];
}
