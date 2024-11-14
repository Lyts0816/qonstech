<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Earnings extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'earnings';

    protected $fillable = [
        'EmployeeID',
        'EarningType',
        'Amount',
        'StartDate',
        'PeriodID',
        'is_disbursed',
        
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }
    public function weekperiod()
    {
        return $this->belongsTo(WeekPeriod::class, 'PeriodID');
    }

    // public function overtime()
    // {
    //     return $this->belongsTo(Overtime::class, 'OvertimeID');
    // }
}
