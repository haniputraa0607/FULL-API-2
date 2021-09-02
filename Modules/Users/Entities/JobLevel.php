<?php

namespace Modules\Users\Entities;

use Illuminate\Database\Eloquent\Model;

class JobLevel extends Model
{
    protected $table = 'job_levels';
    
    protected $primaryKey = 'id_job_level';

	protected $fillable = [
		'job_level_name',
		'id_parent',
        'job_level_visibility',
        'job_level_order'
	];

    public function job_level_parent()
    {
        return $this->belongsTo(JobLevel::class, 'id_parent', 'id_job_level');
    }

    public function job_level_child()
    {
        return $this->hasMany(JobLevel::class, 'id_parent', 'id_job_level');
    }

    public function parent()
    {
        return $this->belongsTo(JobLevel::class, 'id_parent');
    }
}
