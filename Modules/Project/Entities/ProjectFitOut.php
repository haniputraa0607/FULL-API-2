<?php

namespace Modules\Project\Entities;

use App\Http\Models\City;
use Illuminate\Database\Eloquent\Model;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;

class ProjectFitOut extends Model
{
    protected $primaryKey = 'id_projects_fit_out';
    protected $table = 'projects_fit_out';
    protected $fillable = [ 
        'id_project',
        'title',
        'progres',
        'note',
        'attachment',
        'status',
        'created_at',
        'updated_at'
    ];
}