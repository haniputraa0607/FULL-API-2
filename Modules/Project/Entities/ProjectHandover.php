<?php

namespace Modules\Project\Entities;

use App\Http\Models\City;
use Illuminate\Database\Eloquent\Model;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;

class ProjectHandover extends Model
{
    protected $primaryKey = 'id_projects_handover';
    protected $table = 'projects_handover';
    protected $fillable = [ 
        'id_project',
        'title',
        'note',
        'tanggal_serah_terima',
        'soft_opening',
        'grand_opening',
        'status',
        'attachment',
        'created_at',
        'updated_at'
    ];
}