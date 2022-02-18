<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Lib\MyHelper;
/**
 * Class Setting
 * 
 * @property int $id_setting
 * @property string $key
 * @property string $value
 * @property string $value_text
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class Setting extends Model
{
	protected $primaryKey = 'id_setting';

	protected $fillable = [
		'key',
		'value',
		'value_text'
	];
        public static function mid_date(){
            return MyHelper::setting('hs_income_delivery_cut_off_middle_date', 'value', 11);
        }
        public static function end_date(){
           return MyHelper::setting('hs_income_delivery_cut_off_end_date', 'value', 25);
        }
}
