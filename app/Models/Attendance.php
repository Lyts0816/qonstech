<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'Employee_ID',
        'Checkin_One',
        'Checkout_One',
        'Checkin_Two',
        'Checkout_Two',
        'Date',
        'Total_Hours',
        'overtime_in',
        'overtime_out',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'Employee_ID');
    }
}
