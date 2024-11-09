<?php
 
namespace App\Filament\Pages;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
 
class Dashboard extends \Filament\Pages\Dashboard
{
    // public static function canAccess(): bool
    // {
    // return  Auth::user()->role === User::ROLE_ADMIN || Auth::user()->role === User::ROLE_PROJECTCLERK;
    // }

    protected static ?string $title = 'Home';

    
}