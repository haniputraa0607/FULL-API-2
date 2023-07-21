<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;

class PosDevice extends Model
{
    protected $primaryKey = 'id_pos_device';

	protected $casts = [
		'id_outlet' => 'int'
	];

	protected $hidden = [
		'device_token'
	];

	protected $fillable = [
		'id_outlet',
		'device_type',
		'device_id',
		'device_token'
	];

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}
}
