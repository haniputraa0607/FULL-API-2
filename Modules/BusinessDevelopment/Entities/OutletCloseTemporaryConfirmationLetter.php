<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCloseTemporaryConfirmationLetter extends Model
{
    protected $table = 'outlet_close_temporary_confirmation_letter';
	protected $primaryKey = "id_outlet_close_temporary_confirmation_letter";

	protected $fillable = [
        'id_outlet_close_temporary',
		'no_letter',
		'location',
        'date',
        'attachment'
	];
}
