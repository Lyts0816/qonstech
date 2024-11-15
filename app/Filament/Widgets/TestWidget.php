<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Project;
use Filament\Forms\Components\Section as ComponentsSection;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\Section;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TestWidget extends BaseWidget
{

    protected static ?int $sort = 3;
    protected function getStats(): array
    {
        return [
            Stat::make('Total Employees', Employee::count())
            ->icon('heroicon-o-users')
            ->color('success')
            ->description('Total number of employees in the system.'),

            Stat::make('Total Regular Employees', Employee::where('employment_type', 'Regular')->count())
            ->icon('heroicon-o-users')
            ->color('success')
            ->description('Total number of regular employees in the system.'),

            Stat::make('Total Project Based Employees', Employee::where('employment_type', 'Contractual')->count())
            ->icon('heroicon-o-users')
            ->color('success')
            ->description('Total number of project Based employees in the system.'),
        ];
    }
}
