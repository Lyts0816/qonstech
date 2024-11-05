<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Support\Enums\ActionSize;
use Filament\Actions;
use Filament\Notifications\Notification;
Use \Filament\Actions\Action;
use Filament\Actions\ActionGroup;
Use modalSubmitActionLabel;
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

            // ActionGroup::make([
                
            //     Action::make('assignEmployee')
            //     ->label('Assign Employees To Project')
            //     ->form([
            //         Select::make('employees')
            //         ->label('Select Employees')
            //         ->options(
            //             \App\Models\Employee::query()
            //                 ->pluck('first_name', 'id') 
            //                 ->toArray()
            //         )
            //         ->searchable()
            //         ->multiple() 
            //         ->preload()
            //         ->required(),
    
            //         Select::make('project_id')
            //         ->label('Select Project')
            //         ->relationship('project', 'ProjectName')
            //         ->searchable()
            //         ->preload()
            //         ->required()
            //         ,
            //     ])
            //     ->action(function (array $data, Collection $records) {
            //         $projectId = $data['project_id'];
    
            //         $employeeIds = $data['employees'];
    
                
            //         foreach ($employeeIds as $employeeId) {
            //         $employee = \App\Models\Employee::findOrFail($employeeId);
            //         $employee->update(['project_id' => $projectId]);
            //         }
    
            //         Notification::make()
            //         ->body('Employees have been successfully assigned to the project.')
            //         ->success()
            //         ->send()
            //         ;
            //     })
            //     ->modalHeading('Assign Employee To Project')
            //     ->modalSubmitActionLabel('Assign')
            //     ->modalWidth('lg'),
    
    
    
            //     Action::make('assignEmployeess')
            //     ->label('Assign Employees To Position')
            //     ->form([
            //                 Select::make('employees')
            //             ->label('Select Employees')
            //             ->options(
            //                 \App\Models\Employee::query()
            //                     ->pluck('first_name', 'id') 
            //                     ->toArray()
            //             )
            //             ->searchable()
            //             ->multiple()
            //             ->preload()
            //             ->required(),
    
            //             Select::make('position_id')
            //             ->label('Select Position')
            //             ->relationship('position', 'PositionName')
            //             ->searchable()
            //             ->preload(),
            //     ])
            //     ->action(function (array $data, Collection $records) {
            //         $positionId = $data['position_id'];
    
            //         $employeeIds = $data['employees'];
    
                    
            //         foreach ($employeeIds as $employeeId) {
            //         $employee = \App\Models\Employee::findOrFail($employeeId);
            //         $employee->update(['position_id' => $positionId]);
            //         }
    
            //         Notification::make()
            //         ->body('Employees Position Updated Successfully.')
            //         ->success()
            //         ->send();
            //     })
            //     ->modalHeading('Assign Employee To Position')
            //     ->modalSubmitActionLabel('Assign')
            //     ->modalWidth('lg'),
    
    
    
            //     Action::make('assignEmployees')
            //     ->label('Assign Employees To Work Schedule')
            //     ->form([
            //                 Select::make('employees')
            //             ->label('Select Employees')
            //             ->options(
            //                 \App\Models\Employee::query()
            //                     ->pluck('first_name', 'id') 
            //                     ->toArray()
            //             )
            //             ->searchable()
            //             ->multiple()
            //             ->preload()
            //             ->required(),
    
            //             Select::make('schedule_id')
            //             ->label('Work Schedule')
            //             ->required(fn (string $context) => $context === 'create')
            //             ->relationship('schedule', 'ScheduleName')
            //             ->searchable()
            //             ->required()
            //             ->preload(),
            //     ])
            //     ->action(function (array $data, Collection $records) {
            //         $schedId = $data['schedule_id'];
    
            //         $employeeIds = $data['employees'];
    
                    
            //         foreach ($employeeIds as $employeeId) {
            //         $employee = \App\Models\Employee::findOrFail($employeeId);
            //         $employee->update(['schedule_id' => $schedId]);
            //         }
    
            //         Notification::make()
            //         ->body('Employees Schedule Updated Successfully.')
            //         ->success()
            //         ->send();
            //     })
            //     ->modalHeading('Assign Employee Work Schedule')
            //     ->modalSubmitActionLabel('Assign')
            //     ->modalWidth('lg'),


            //     Action::make('assignEmployeessss')
            //     ->label('Assign Employees To Overtime')
            //     ->form([
            //                 Select::make('employees')
            //             ->label('Select Employees')
            //             ->options(
            //                 \App\Models\Employee::query()
            //                     ->pluck('first_name', 'id') 
            //                     ->toArray()
            //             )
            //             ->searchable()
            //             ->multiple()
            //             ->preload()
            //             ->required(),
    
            //             Select::make('overtime_id')
            //             ->label('Overtime')
            //             ->required(fn (string $context) => $context === 'create')
            //             ->relationship('overtime', 'Reason')
            //             ->searchable()
            //             ->required()
            //             ->preload(),
            //     ])
            //     ->action(function (array $data, Collection $records) {
            //         $schedId = $data['overtime_id'];
    
            //         $employeeIds = $data['employees'];
    
                    
            //         foreach ($employeeIds as $employeeId) {
            //         $employee = \App\Models\Employee::findOrFail($employeeId);
            //         $employee->update(['overtime_id' => $schedId]);
            //         }
    
            //         Notification::make()
            //         ->body('Employees Assigned To Overtime Successfully.')
            //         ->success()
            //         ->send();
            //     })
            //     ->modalHeading('Assign Employee to Overtime')
            //     ->modalSubmitActionLabel('Assign')
            //     ->modalWidth('lg')

            // ])->label('Assign Employees')->button()->size(ActionSize::Medium),
            
        

        ];

    }
    public function getTabs(): array
    {
        return [
            // 'All' => Tab::make(),

            'activeEmployees' => Tab::make('Active Employees')
            ->badge(Employee::whereNull('deleted_at')->count()) 
            ->modifyQueryUsing(function ($query) {
                $query->whereNull('deleted_at'); 
            }),

            'Available' => Tab::make()->modifyQueryUsing(function ($query){
                $query->where('status', 'Available');
            }),

            'Assigned' => Tab::make()->modifyQueryUsing(function ($query){
                $query->where('status', 'Assigned');
            }),

            'Regular' => Tab::make()->modifyQueryUsing(function ($query){
                $query->where('employment_type', 'Regular');
            }),

            'Main Office' => Tab::make()->modifyQueryUsing(function ($query){
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
