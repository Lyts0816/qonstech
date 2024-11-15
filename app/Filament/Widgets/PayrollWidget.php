<?php

namespace App\Filament\Widgets;

use App\Models\Payroll;
use App\Models\Payslip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayrollWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected function getStats(): array
    {
        return [
            Stat::make('Total Payroll Generated', Payroll::count())
            ->icon('heroicon-o-credit-card')
            ->color('success')
            ->description('Total payroll Generated for the month.'),

            Stat::make('Total Payslip Generated', Payslip::count())
            ->icon('heroicon-o-credit-card')
            ->color('success')
            ->description('Total amount of payslip generated for the month.'),
        ];
    }
}
