<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'payroll';

    protected $fillable = [
        'EmployeeID',
        'PayrollDate',
        'TotalEarnings',
        'GrossPay',
        'TotalDeductions',
        'NetPay',
        'PayrollDate2',
        'PayrollFrequency',
        'EmployeeStatus',
        'assignment',
        'PayrollMonth',
        'PayrollYear',
        'ProjectID',
        'weekPeriodID',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'ProjectID');
    }

    public function dates()
    {
        return $this->hasMany(Payroll::class);
    }
}
