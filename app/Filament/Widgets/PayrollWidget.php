<?php

namespace App\Filament\Widgets;

use App\Models\Payroll;
use App\Models\Payslip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PayrollWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
    return  Auth::user()->role === User::ROLE_ADMIN || Auth::user()->role === User::ROLE_ADMINUSER || Auth::user()->role === User::ROLE_FIVP;
    // return false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Payroll Generated', Payroll::count())
            ->icon('heroicon-o-credit-card')
            ->color('success')
            ->description('Total number of payroll generated'),

            Stat::make('Total Payslip Generated', Payslip::count())
            ->icon('heroicon-o-credit-card')
            ->color('success')
            ->description('Total number of payslip generated'),
        ];
    }
}
