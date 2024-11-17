<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'refcitymun'; // Specify the table name
    protected $primaryKey = 'citymunCode'; // Primary key
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'psgcCode',
        'citymunDesc',
        'regDesc',
        'provCode',
        'citymunCode',
    ];
}
