<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pagibig extends Model
{
    use HasFactory;

    protected $table = 'pagibig';

    protected $fillable = [
        'MinimumSalary',
        'MaximumSalary',
        'EmployeeRate',
        'EmployerRate',
    ];


    public function calculateContribution($salary)
    {
        // Determine rate based on salary
        if ($salary < 1500) {
            $rate = 1; // 1% if salary is below 1500
        } else {
            $rate = 2; // 2% if salary is 1500 or more
        }

        return $salary * ($rate / 100);
    }
}
