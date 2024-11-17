<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OvertimeSchedule extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'overtime';

    protected $fillable = [
        'Reason',
        'EmployeeID',
        'Checkin',
        'Checkout',
        'Date',
        'Status',
    ];

    // Define the relationship with the Employee model
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID', 'id');
    }
    
    

    
}
