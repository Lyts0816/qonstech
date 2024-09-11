<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
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
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

}
