<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistAnnouncement extends Model
{
	protected $primaryKey = 'id_hairstylist_announcement';

	protected $dates = [
		'date_start',
		'date_end'
	];

	protected $fillable = [
		'date_start',
		'date_end',
		'content'
	];
}
