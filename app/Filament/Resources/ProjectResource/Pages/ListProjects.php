<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use App\Filament\Pages\ShowAvailableEmployees;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Action::make('customPage')
            ->label('Assign Employees')
            ->color('success')
            ->url(ShowAvailableEmployees::getUrl())
            ->visible(Auth::user()->role === 'Human Resource')


        ];
    }

    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Active Projects')
            ->badge(Project::whereNull('deleted_at')->count()) 
            ->modifyQueryUsing(function ($query) {
                $query->whereNull('deleted_at'); 
            }),

            'archive' => Tab::make('Deactivated Projects')
            ->badge(Project::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                $query->onlyTrashed();
            }),
        ];
    }
}
