<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeInbox extends Model
{
    protected $table = 'employee_inboxes';
    protected $primaryKey = 'id_employee_inboxes';
    protected $fillable = [
        'id_campaign',
        'id_employee',
        'inboxes_subject',
        'inboxes_content',
        'inboxes_clickto',
        'inboxes_link',
        'inboxes_id_reference',
        'inboxes_category',
        'inboxes_from',
        'inboxes_send_at',
        'read',
        'id_brand'
    ];

    public function campaign()
	{
		return $this->belongsTo(\App\Http\Models\Campaign::class, 'id_campaign');
	}

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_employee');
	}
}
