<?php

namespace Modules\Recruitment\Entities;

use App\Http\Models\Outlet;
use Illuminate\Database\Eloquent\Model;

class RequestHairStylist extends Model
{
    protected $table = 'request_hair_stylists';
	protected $primaryKey = "id_request_hair_stylist";
	protected $fillable = [
        'id_outlet',
		'number_of_request',
		'status',
		'applicant',
		'notes'
	];
    public function outlet_request(){
        return $this->belongsTo(Outlet::class, 'id_outlet');
    }

}
