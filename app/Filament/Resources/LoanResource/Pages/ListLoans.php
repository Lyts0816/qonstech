<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use App\Models\Loan;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListLoans extends ListRecords
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }


    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Active Loan')
                ->badge(Loan::whereNull('deleted_at')->count())
                ->modifyQueryUsing(function ($query) {
                    $query->whereNull('deleted_at');
                }),

            'archive' => Tab::make('Deactivated Loan')
                ->badge(Loan::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    $query->onlyTrashed();
                }),
        ];
    }
}
