<?php

namespace Modules\Enquiries\Entities;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $primaryKey = 'id_ticket';

	protected $table = 'tickets';

	protected $fillable = [
		'phone',
        'id_user',
		'id_ticket_third_party',
	];
}
