<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected function getStats(): array
    {
        return [
            Stat::make('Total Projects', Project::count())
            ->icon('heroicon-o-briefcase')
            ->color('success')
            ->description('Total number of projects in the system.'),

            Stat::make('On Going Projects', Project::where('status', 'On Going')->count())
            ->icon('heroicon-o-briefcase')
            ->color('success')
            ->description('Total number of on going projects in the system.'),

            Stat::make('Completed Projects', Project::where('status', 'Complete')->count())
            ->icon('heroicon-o-briefcase')
            ->color('success')
            ->description('Total number of completed projects in the system.'),

            Stat::make('Incomplete Projects', Project::where('status', 'Incomplete')->count())
            ->icon('heroicon-o-briefcase')
            ->color('success')
            ->description('Total number of incomplete projects in the system.'),
        ];
    }
}
