<?php

namespace App\Filament\Resources\PayslipResource\Pages;

use App\Filament\Resources\PayslipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Resources\Components\Tab;
use App\Models\Payslip;

class ListPayslips extends ListRecords
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Payslip')
                ->badge(Payslip::whereNull('deleted_at')->count())
                ->modifyQueryUsing(function ($query) {
                    $query->whereNull('deleted_at');
                }),

            'archive' => Tab::make('Archived Payslip')
                ->badge(Payslip::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    $query->onlyTrashed();
                }),
        ];
    }
}
