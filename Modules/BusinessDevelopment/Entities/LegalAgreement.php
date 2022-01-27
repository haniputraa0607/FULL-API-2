<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class LegalAgreement extends Model
{
    protected $table = 'legal_agreements';
	protected $primaryKey = "id_legal_agreement";

	protected $fillable = [
        'id_partner',
        'id_location',
		'no_letter',
        'date_letter',
        'attachment'
	];
}
