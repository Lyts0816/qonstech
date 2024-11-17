<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class EmployeePieWidget extends ChartWidget
{
    protected static ?string $heading = 'Employees';
    protected static ?string $description = 'Total number of employees (Regular, Contractual)';
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
    return  Auth::user()->role === User::ROLE_ADMIN || Auth::user()->role === User::ROLE_ADMINUSER || Auth::user()->role === User::ROLE_FIVP || Auth::user()->role === User::ROLE_PROJECTCLERK;
    // return false;
    }

    protected function getData(): array
    {
        $regularCount = Employee::where('employment_type', 'Regular')->count();
        $contractualCount = Employee::where('employment_type', 'Contractual')->count();

        return [
            'labels' => ['Regular', 'Contractual'],
            'datasets' => [
                [
                    'data' => [$regularCount, $contractualCount],
                    'backgroundColor' => ['#007BFF', '#FFC107'],
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
