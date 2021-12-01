<?php

namespace Modules\Project\Entities;

use App\Http\Models\City;
use Illuminate\Database\Eloquent\Model;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;

class ProjectSurveyLocation extends Model
{
    protected $primaryKey = 'id_projects_survey_location';
    protected $table = 'projects_survey_location';
    protected $fillable = [ 
        'surveyor', 
        'location_length', 
        'location_large', 
        'location_width', 
        'id_project', 
        'survey_date', 
        'note',
        'attachment',
        'status', 
        'created_at',
        'updated_at',
        'kondisi', 
        'keterangan_kondisi', 
        'listrik', 
        'keterangan_listrik', 
        'ac', 
        'keterangan_ac', 
        'air',
        'keterangan_air',
        'internet', 
        'keterangan_internet',
        'line_telepon', 
        'keterangan_line_telepon', 
        'nama_pic_mall', 
        'cp_pic_mall', 
        'nama_kontraktor', 
        'cp_kontraktor', 
        'tanggal_mulai_pekerjaan', 
        'tanggal_selesai_pekerjaan',
        'tanggal_loading_barang',
        'area_lokasi', 
        'tanggal_pengiriman_barang',
        'estimasi_tiba',
    ];
}