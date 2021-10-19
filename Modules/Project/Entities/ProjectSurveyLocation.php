<?php

namespace Modules\Project\Entities;

use App\Http\Models\City;
use Illuminate\Database\Eloquent\Model;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;

class ProjectSurveyLocation extends Model
{
    protected $primaryKey = 'id_projects_survey_location';
    protected $table = 'projects_survey_location';
    protected $fillable = [ 
        'surveyor', 
        'location_length', 
        'location_large', 
        'location_width', 
        'id_project', 
        'survey_date', 
        'note',
        'attachment',
        'status', 
        'created_at',
        'updated_at'
    ];
}