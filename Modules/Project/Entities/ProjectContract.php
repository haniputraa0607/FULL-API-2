<?php

namespace Modules\Project\Entities;

use App\Http\Models\City;
use Illuminate\Database\Eloquent\Model;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;

class ProjectContract extends Model
{
    protected $primaryKey = 'id_projects_contract';
    protected $table = 'projects_contract';
    protected $fillable = [ 
        'id_project',
        'first_party',
        'second_party',
        'nominal',
        'note',
        'attachment',
        'created_at',
        'updated_at'
    ];
}