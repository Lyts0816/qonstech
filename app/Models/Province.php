<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table = 'refprovince'; // Specify the table name
    protected $primaryKey = 'provCode'; // Specify the primary key if it's not `id`
    public $incrementing = false; // If `provCode` is not auto-incrementing
    protected $keyType = 'string'; // Specify the type of primary key if it's not an integer
    
    protected $fillable = [
        'psgcCode',
        'provDesc',
        'regCode',
        'provCode',
    ];
}
