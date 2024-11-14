<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
	use SoftDeletes;
	use HasFactory;

	protected $table = 'holidays';

	protected $fillable = [
		'HolidayName',
		'HolidayDate',
		'HolidayType',
		'ProjectID'
	];

	public function project()
	{
		return $this->belongsTo(Project::class, 'ProjectID');
	}

}
