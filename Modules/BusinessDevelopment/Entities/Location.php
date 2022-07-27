<?php

namespace Modules\BusinessDevelopment\Entities;

use App\Http\Models\City;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $primaryKey = 'id_location';
    protected $fillable = [ 
        'name', 
        'address', 
        'id_city', 
        'latitude', 
        'longitude',
        'pic_name',
        'pic_contact',
        'id_partner',
        'start_date',
        'end_date',
        'status',
        'location_large',
        'rental_price',
        'service_charge',
        'promotion_levy',
        'renovation_costs',
        'partnership_fee',
        'income',
        'notes',
        'step_loc',
        'is_tax',
        'date_loi',
        'no_loi',
        'submited_by',
        "width",
        "height",
        "length",
        "location_type",
        "location_image",
        "id_outlet_starter_bundling",
        "total_box",
        "handover_date",
        "no_spk",
        "date_spk",
        "id_term_of_payment",
        "ownership_status",
        "cooperation_scheme",
        "sharing_percent",
        "sharing_value",
        "location_notes",
        "company_type",
        'code',
        'id_branch',
        'id_branch_ima',
        'email'

    ];
    public function location_partner(){
        return $this->belongsTo(Partner::class, 'id_partner');
    }
    public function submited(){
        return $this->belongsTo(Partner::class, 'submited_by');
    }
    public function location_city(){
        return $this->belongsTo(City::class, 'id_city');
    }
    public function location_step(){
        return $this->hasMany(StepLocationsLog::class, 'id_location');
    }
    public function location_survey(){
        return $this->hasMany(FormSurvey::class, 'id_location');
    }
    public function location_confirmation(){
        return $this->hasOne(ConfirmationLetter::class, 'id_location');
    }
    public function outlet_starter(){
        return $this->belongsTo(OutletStarterBundling::class, 'id_outlet_starter_bundling');
    }
    public function location_starter(){
        return $this->hasMany(LocationOutletStarterBundlingProduct::class, 'id_location');
    }
    public function location_init(){
        return $this->hasOne(InitBranch::class, 'id_location');
    }

}
