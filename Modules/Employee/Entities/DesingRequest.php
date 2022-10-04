<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\User;

class DesingRequest extends Model
{
    protected $table = 'design_requests';
    protected $primaryKey = 'id_design_request';

	protected $dates = [
		'required_date',
		'estimated_date',
		'update_status_date',
	];

	protected $fillable = [
		'id_request',
		'title',
		'required_date',
		'required_note',
		'id_approve',
		'update_status_date',
		'estimated_date',
		'design_path',
		'finished_note',
		'status',
        'created_at',
        'updated_at',

	];

    public function approve(){
        return $this->belongsTo(User::class, 'id_approve', 'id');
    }

    public function request(){
        return $this->belongsTo(User::class, 'id_request', 'id');
    }
}
