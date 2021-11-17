<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCloseTemporaryFormSurvey extends Model
{
    protected $table = 'outlet_close_temporary_form_survey';
	protected $primaryKey = "id_outlet_close_temporary_form_survey";

	protected $fillable = [
        'id_outlet_close_temporary',
		'survey',
        'surveyor',
        'potential',
        'note',
        'survey_date',
        'attachment'
	];
}
