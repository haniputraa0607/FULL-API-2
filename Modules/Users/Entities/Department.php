<?php

namespace Modules\Users\Entities;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
	protected $primaryKey = 'id_department';

	protected $casts = [
		'id_parent' => 'int'
	];

	protected $fillable = [
		'department_name',
		'id_parent'
	];

	public function parent()
    {
        return $this->belongsTo(Department::class, 'id_parent');
    }
}
