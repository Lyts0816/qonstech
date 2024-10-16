<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'PositionName',
        'MonthlySalary',
        'HourlyRate',
    ];

		public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
