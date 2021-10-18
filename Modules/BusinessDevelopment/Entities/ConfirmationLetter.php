<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class ConfirmationLetter extends Model
{
    protected $table = 'confirmation_letters';
	protected $primaryKey = "id_confirmation_letter";

	protected $fillable = [
        'id_partner',
		'no_letter',
		'location',
        'date',
        'attachment'
	];
}
