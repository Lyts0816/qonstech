<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'projects';

    protected $fillable = [
        'ProjectName',
        'PR_Street',
        'PR_Barangay',
        'PR_City',
        'PR_Province',
        'StartDate',
        'EndDate',
        'Status',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function payroll()
    {
        return $this->hasMany(Payroll::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'PR_Province', 'provDesc'); // Maps the name directly
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'PR_City', 'citymunDesc');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'PR_Barangay', 'brgyDesc');
    }


}
