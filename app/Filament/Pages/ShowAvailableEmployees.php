<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Livewire\Employees;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ShowAvailableEmployees extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.show-available-employees';
    protected static ?string $navigationGroup = "Projects/Assign";
    protected static ?string $title = 'Add Employees to Project';

    public static function canAccess(): bool
    {
        return  Auth::user()->role === User::ROLE_ADMIN || Auth::user()->role === User::ROLE_PROJECTCLERK;
    }

    protected function getWidgets(): array
    {
        return [
            Employees::class,
        ];
    }
}
