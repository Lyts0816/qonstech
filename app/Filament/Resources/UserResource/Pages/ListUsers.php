<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        $tabs['activeEmployees'] = Tab::make('Active Users')
        ->badge(User::whereNull('deleted_at')->count()) 
        ->modifyQueryUsing(function ($query) {
            $query->whereNull('deleted_at'); 
        });

        $tabs['archived'] = Tab::make('Deactivated Users')
            ->badge(User::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                $query->onlyTrashed();
            });
        

        return $tabs;
    }
}
