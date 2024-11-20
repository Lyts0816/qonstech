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
        if ($salary < 1500) {
            $rate = 1;
        } else {
            $rate = 2;
        }
        return $salary * ($rate / 100);
    }
}
