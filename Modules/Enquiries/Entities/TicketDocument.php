<?php

namespace Modules\Enquiries\Entities;

use Illuminate\Database\Eloquent\Model;

class TicketDocument extends Model
{
    protected $primaryKey = 'id_ticket_document';

	protected $table = 'tickets_documents';

	protected $fillable = [
		'id_ticket',
        'attachment',
	];
}
