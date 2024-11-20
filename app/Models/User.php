<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use SoftDeletes;
    use Notifiable;

    const ROLE_ADMIN = 'Human Resource';
    const ROLE_PROJECTCLERK = 'Project Clerk';
    const ROLE_ADMINUSER = 'Admin Vice President';
    const ROLE_FIVP = 'Finance Vice President';

    const ROLES = [
        self::ROLE_ADMIN => 'Human Resource',

        self::ROLE_PROJECTCLERK => 'Project Clerk',
    ];

    public function isVPFI()
    {
        return $this->role === self::ROLE_ADMINUSER || $this->role === self::ROLE_FIVP;
    }

    public function isCLerk()
    {
        return $this->role === self::ROLE_PROJECTCLERK;
    }

    public function isHR()
    {
        return $this->role === self::ROLE_ADMIN;
    }


    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMINUSER || $this->role === self::ROLE_ADMIN;
    }

    public function isAdVP()
    {
        return $this->role === self::ROLE_ADMINUSER;
    }

    public function isFiVp()
    {
        return $this->role === self::ROLE_FIVP;
    }

    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'EmployeeID',
        'Username',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsTo(Role::class);
    }
}
