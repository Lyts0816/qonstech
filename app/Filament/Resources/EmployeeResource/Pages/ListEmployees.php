<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Support\Enums\ActionSize;
use Filament\Actions;
use Filament\Notifications\Notification;
use \Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use modalSubmitActionLabel;
use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

        ];
    }
    public function getTabs(): array
    {
        return [


            'activeEmployees' => Tab::make('Active Employees')
                ->badge(Employee::whereNull('deleted_at')->count())
                ->modifyQueryUsing(function ($query) {
                    $query->whereNull('deleted_at');
                }),

            'Available' => Tab::make()->modifyQueryUsing(function ($query) {
                $query->where('status', 'Available');
            }),

            'Assigned' => Tab::make()->modifyQueryUsing(function ($query) {
                $query->where('status', 'Assigned');
            }),

            'Regular' => Tab::make()->modifyQueryUsing(function ($query) {
                $query->where('employment_type', 'Regular');
            }),

            'Main Office' => Tab::make()->modifyQueryUsing(function ($query) {
                $query->where('assignment', 'Main Office');
            }),

            'archive' => Tab::make('Deactivated Employees')
                ->badge(Employee::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    $query->onlyTrashed();
                }),
        ];
    }
}
