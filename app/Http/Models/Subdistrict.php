<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Subdistrict extends Model
{
	protected $primaryKey = 'id_subdistrict';
	public $timestamps = false;

	protected $casts = [
		'id_city' => 'int'
	];

	protected $fillable = [
		'id_city',
		'subdistrict_name'
	];

	public function city()
	{
		return $this->belongsTo(\App\Http\Models\City::class, 'id_city');
	}
}
