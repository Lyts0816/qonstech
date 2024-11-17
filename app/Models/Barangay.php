<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    protected $table = 'refbrgy'; // Specify the table name
    protected $primaryKey = 'brgyCode'; // Primary key
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'brgyCode',
        'brgyDesc',
        'regCode',
        'provCode',
        'citymunCode',
    ];
}
