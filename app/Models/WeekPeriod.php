<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTime;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeekPeriod extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'weekperiod';

    protected $fillable = [
        'StartDate',
        'EndDate',
        'Month',
        'Year',
        'Category',
        'Type',
    ];

    public function getTypeWithMonthAttribute()
    {
        $monthName = DateTime::createFromFormat('!m', $this->Month)->format('F');
        return "{$this->Type} - {$monthName}";
    }
}
