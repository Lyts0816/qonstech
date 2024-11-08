<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $fillable = [
        'EmployeeID',
        'PayrollDate',
        'ReportType',
        'TotalEarnings',
        'GrossPay',
        'TotalDeductions',
        'assignment',
        'NetPay',
        'PayrollDate2',
        'PayrollFrequency',
        'EmployeeStatus',
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

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    // public function dates()
    // {
    //     return $this->hasMany(PayrollDate::class); // Adjust class name if necessary
    // }
}
