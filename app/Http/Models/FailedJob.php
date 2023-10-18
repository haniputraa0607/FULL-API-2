<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id_log_activity
 * @property integer $id_user
 * @property string $module
 * @property string $action
 * @property string $request
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 */
class FailedJob extends Model
{
	/**
	 * The database name used by the model.
	 *
	 * @var string
	 */
	protected $connection = 'mysql';
	
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'failed_jobs';

    /**
     * @var array
     */

}
