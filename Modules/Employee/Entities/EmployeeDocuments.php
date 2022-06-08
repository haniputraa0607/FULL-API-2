<?php

namespace Modules\Employee\Entities;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;

class EmployeeDocuments extends Authenticatable
{
    protected $table = 'employee_documents';

    protected $primaryKey = 'id_employee_document';

    protected $fillable   = [
        'id_employee',
        'document_type',
        'process_date',
        'process_name_by',
        'process_notes',
        'attachment',
    ];

    public function getAttachmentAttribute($value)
    {
        if(empty($value)){
            return '';
        }
        return config('url.storage_url_api') . $value;
    }
}
