<?php

namespace App\Filament\Resources\EarningsResource\Pages;

use App\Filament\Resources\EarningsResource;
use Filament\Actions;
use App\Models\Earnings;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListEarnings extends ListRecords
{
    protected static string $resource = EarningsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Active Earnings')
                ->badge(Earnings::whereNull('deleted_at')->count())
                ->modifyQueryUsing(function ($query) {
                    $query->whereNull('deleted_at');
                }),

            'archive' => Tab::make('Deactivated Earnings')
                ->badge(Earnings::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    $query->onlyTrashed();
                }),
        ];
    }
}
