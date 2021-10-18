<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;

class UserHairStylistDocuments extends Authenticatable
{
    protected $table = 'user_hair_stylist_documents';

    protected $primaryKey = 'id_user_hair_stylist_document';

    protected $fillable   = [
        'id_user_hair_stylist',
        'document_type',
        'process_date',
        'process_name_by',
        'process_notes',
        'attachment'
    ];

    public function getAttachmentAttribute($value)
    {
        if(empty($value)){
            return '';
        }
        return config('url.storage_url_api') . $value;
    }


}
