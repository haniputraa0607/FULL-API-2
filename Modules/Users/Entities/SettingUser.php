<?php

namespace Modules\Users\Entities;

use Illuminate\Database\Eloquent\Model;

class SettingUser extends Model
{
    protected $table = 'setting_users';
    protected $primaryKey = 'id_setting_user';

	protected $fillable = [
		'id',
		'key',
        'value',
        'value_text',
	];

	public function office_hour(){
        return $this->belongsTo(\App\Http\Models\User::class, 'id');
    }
}
