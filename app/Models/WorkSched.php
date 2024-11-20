<?php

namespace App\Models;

use Doctrine\DBAL\Schema\Table;
use Filament\Forms\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkSched extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'worksched';

    protected $fillable = [
        'ScheduleName',
        'RegularHours',
        'ScheduleType',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'CheckinOne',
        'CheckoutOne',
        'CheckinTwo',
        'CheckoutTwo',
    ];

    protected $casts = [
        'monday' => 'boolean',
        'tuesday' => 'boolean',
        'wednesday' => 'boolean',
        'thursday' => 'boolean',
        'friday' => 'boolean',
        'saturday' => 'boolean',
        'sunday' => 'boolean',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'schedule_id');
    }
}
