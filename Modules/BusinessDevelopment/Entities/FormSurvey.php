<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class FormSurvey extends Model
{
    protected $table = 'form_surveys';
	protected $primaryKey = "id_form_survey";

	protected $fillable = [
        'id_partner',
		'survey',
        'surveyor',
        'potential',
        'note',
        'survey_date',
        'attachment'
	];
}
