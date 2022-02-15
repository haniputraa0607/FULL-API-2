<?php

namespace Modules\Project\Entities;

use App\Http\Models\City;
use Illuminate\Database\Eloquent\Model;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\Project\Entities\ProjectSurveyLocation;
use Modules\Project\Entities\ProjectDesain;
use Modules\Project\Entities\ProjectContract;
use Modules\Project\Entities\ProjectFitOut;
use Modules\Project\Entities\ProjectHandover;
use Modules\Project\Entities\InvoiceSpk;
use Modules\Project\Entities\InvoiceBap;
use Modules\Project\Entities\PurchaseSpk;

class Project extends Model
{
    protected $primaryKey = 'id_project';
//    protected $table = 'projects';
    protected $fillable = [ 
        'name', 
        'id_location', 
        'id_partner', 
        'start_project', 
        'status', 
        'note', 
        'created_at',
        'updated_at' 
    ];
    public function project_locations(){
        return $this->belongsTo(Location::class, 'id_location');
    }
    public function project_partners(){
        return $this->belongsTo(Partner::class, 'id_partner');
    }
    public function project_survey(){
        return $this->hasOne(ProjectSurveyLocation::class, 'id_project');
    }
    public function project_desain(){
        return $this->hasMany(ProjectDesain::class, 'id_project');
    }
    public function project_fitout(){
        return $this->hasMany(ProjectFitOut::class, 'id_project')->orderby('created_at','DESC');
    }
    public function project_contract(){
        return $this->hasOne(ProjectContract::class, 'id_project');
    }
    public function project_handover(){
        return $this->hasOne(ProjectHandover::class, 'id_project');
    }
    public function invoice_spk(){
        return $this->hasOne(InvoiceSpk::class, 'id_project');
    }
    public function invoice_bap(){
        return $this->hasOne(InvoiceBap::class, 'id_project');
    }
    public function purchase_spk(){
        return $this->hasOne(PurchaseSpk::class, 'id_project');
    }
}
