<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PayrollPieWidget extends ChartWidget
{
    protected static ?int $sort = 1;
    protected static ?string $heading = 'Projects';
    protected static ?string $description = 'Total number of projects (On going, Incomplete, Complete)';

    public static function canView(): bool
    {
    return  Auth::user()->role === User::ROLE_ADMIN || Auth::user()->role === User::ROLE_ADMINUSER || Auth::user()->role === User::ROLE_FIVP;
    // return false;
    }

    protected function getData(): array
    {
        $onGoingCount = Project::where('status', 'On going')->count();
        $incompleteCount = Project::where('status', 'Incomplete')->count();
        $completeCount = Project::where('status', 'Complete')->count();

        return [
            'labels' => ['On going', 'Incomplete', 'Complete'],
            'datasets' => [
                [
                    'data' => [$onGoingCount, $incompleteCount, $completeCount],
                    'backgroundColor' => ['#007BFF', '#FFC107', '#28A745'],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
