<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletChangeLocationFormSurvey extends Model
{
        protected $table = 'outlet_change_location_form_survey';
	protected $primaryKey = "id_outlet_change_location_form_survey";

	protected $fillable = [
                'id_outlet_change_location',
                'survey',
                'surveyor',
                'potential',
                'note',
                'survey_date',
                'attachment',
                'created_at',
                'updated_at' 
	];
} 
