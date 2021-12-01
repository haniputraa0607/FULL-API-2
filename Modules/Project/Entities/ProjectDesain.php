<?php

namespace Modules\Project\Entities;

use App\Http\Models\City;
use Illuminate\Database\Eloquent\Model;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;

class ProjectDesain extends Model
{
    protected $primaryKey = 'id_projects_desain';
    protected $table = 'projects_desain';
    protected $fillable = [ 
        'id_project',
        'note',
        'nama_designer',
        'cp_designer',
        'desain',
        'attachment',
        'status',
        'created_at',
        'updated_at'
    ];
}