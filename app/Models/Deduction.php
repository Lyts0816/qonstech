<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'deductions';

    
    protected $fillable = [
        'employeeID',
        'DeductionType',
        'Amount',
        'StartDate',
        'PeriodID',
    ];

    // Define the relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeID');
    }

    public function weekperiod()
    {
        return $this->belongsTo(WeekPeriod::class, 'PeriodID');
    }

}
