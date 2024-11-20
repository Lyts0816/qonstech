<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;
    use HasFactory;


    protected $table = 'employees';

    protected $fillable = [
        'position_id',
        'overtime_id',
        'project_id',
        'schedule_id',
        'first_name',
        'middle_name',
        'last_name',
        'employment_type',
        'assignment',
        'street',
        'barangay',
        'city',
        'province',
        'contact_number',
        'status',
        'SSSNumber',
        'PhilHealthNumber',
        'PagibigNumber',
        'TaxIdentificationNumber'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'EmployeeID');
    }

    // Defining the relationships

    /**
     * Get the position associated with the employee.
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the project associated with the employee.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the schedule associated with the employee.
     */
    public function schedule()
    {
        return $this->belongsTo(WorkSched::class, 'schedule_id');
    }

    public function overtime()
    {
        return $this->belongsTo(Overtime::class, 'overtime_id');
    }

    /**
     * Get the full name of the employee.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->middle_name} {$this->last_name}";
    }

    public function attendanceRecords()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'PR_Province', 'provDesc');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'PR_City', 'citymunDesc');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'PR_Barangay', 'brgyDesc');
    }

    public function getFullAddressAttribute()
    {
        $streetName = $this->street ?? '';
        $barangayName = $this->barangay ? $this->barangay->brgyDesc : '';
        $cityName = $this->city ? $this->city->citymunDesc : '';
        $provinceName = $this->province ? $this->province->provDesc : '';

        return trim("{$streetName} {$barangayName} {$cityName} {$provinceName}");
    }
}
