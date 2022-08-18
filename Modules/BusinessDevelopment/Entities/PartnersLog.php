<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class PartnersLog extends Model
{
    protected $table = 'partners_logs';
	protected $primaryKey = "id_partners_log";

	protected $fillable = [
        'id_partner',
		'update_name',
		'update_phone',
		'update_email',
		'update_address',
		'update_status'
	];
    public function original_data(){
        return $this->belongsTo(Partner::class, 'id_partner');
    }
}
