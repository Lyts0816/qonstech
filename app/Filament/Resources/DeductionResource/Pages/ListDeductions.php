<?php

namespace App\Filament\Resources\DeductionResource\Pages;

use App\Filament\Resources\DeductionResource;
use Filament\Actions;
use App\Models\Deduction;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListDeductions extends ListRecords
{
    protected static string $resource = DeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Active Deduction')
            ->badge(Deduction::whereNull('deleted_at')->count()) 
            ->modifyQueryUsing(function ($query) {
                $query->whereNull('deleted_at'); 
            }),

            'archive' => Tab::make('Deactivated Deduction')
            ->badge(Deduction::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                $query->onlyTrashed();
            }),
        ];
    }
}
