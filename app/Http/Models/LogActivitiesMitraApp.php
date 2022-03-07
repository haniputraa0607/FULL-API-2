<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogActivitiesMitraApp extends Model
{
    /**
	 * The database name used by the model.
	 *
	 * @var string
	 */
    protected $connection = 'mysql2';
	
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'log_activities_mitra_apps';
    protected $primaryKey = 'id_log_activities_mitra_app';

    /**
     * @var array
     */
    protected $fillable = [
        'url', 
        'subject',  
        'phone',
        'user', 
        'request', 
        'response_status', 
        'response', 
        'ip', 
        'useragent', 
        'created_at', 
        'updated_at'
    ];
}
