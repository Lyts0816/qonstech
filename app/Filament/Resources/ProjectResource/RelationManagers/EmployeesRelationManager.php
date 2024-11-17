<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Pages\ShowAvailableEmployees;
use Filament\Actions\Modal\Actions\Action;
use Filament\Actions\StaticAction;
use App\Models\WorkSched;
use App\Models\Employee;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpParser\Node\Stmt\Label;

use function Laravel\Prompts\form;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    protected function getProjectName(): string
    {
        // Replace this with the actual logic to get the project name
        return 'YourProjectName';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                Tables\Columns\TextColumn::make('id'),

                Tables\Columns\TextColumn::make('full_name') 
                ->label('Name')
                ->formatStateUsing(fn ($record) => $record->first_name . ' ' . ($record->middle_name ? $record->middle_name . ' ' : '') . $record->last_name)
                ->sortable()
                ->searchable(['first_name', 'middle_name', 'last_name']),

                Tables\Columns\TextColumn::make('position.PositionName'),

                Tables\Columns\TextColumn::make('schedule.ScheduleName'),

                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                SelectFilter::make('first_name')
                ->label('Filter by Employee First Name')
                ->options(
                    \App\Models\Employee::query()
                        ->pluck('first_name', 'first_name')
                        ->toArray()
                )
                ->searchable()
                ->multiple()
                ->preload(),

                SelectFilter::make('position_id')
                ->label('Filter by Position')
                ->relationship('position', 'PositionName')
                ->searchable()
                ->multiple()
                ->preload(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('customPage')
                ->label('Assign Employees')
                ->url(ShowAvailableEmployees::getUrl()),
                
            ])

            ->actions([
                // Tables\Actions\Action::make('create')
                //     ->label('Work Schedule')
                //     ->form(fn ($record) => [

                //         Forms\Components\TextInput::make('id')
                //         ->label('Employee ID')
                //         ->default($record->id)
                //         ->readOnly()
                //         ->hidden(),

                //         Forms\Components\TextInput::make('first_name')
                //         ->Label('First Name')
                //         ->default($record->full_name)
                //         ->readOnly(),

                //         Section::make('')
                //         ->schema([
                //             TextInput::make('ScheduleName')
                //             ->label('Schedule Name')
                //             ->required(fn (string $context) => $context === 'create')
                //             ->rules('regex:/^[^\d]*$/'),
        
                //             TextInput::make('RegularHours')
                //             ->label('Regular Hours')
                //             ->numeric()
                //             ->maxLength(2)
                //             ->maxValue(12)
                //         ])->compact()->columns(),
                        
        
                //         Section::make('Days')
                //         ->schema([
                //             Toggle::make('monday'),
                //             Toggle::make('tuesday'),
                //             Toggle::make('wednesday'),
                //             Toggle::make('thursday'),
                //             Toggle::make('friday'),
                //             Toggle::make('saturday'),
                //             Toggle::make('sunday'),
                //         ])->compact()->columns(7)->collapsible(true),
                        
                //         Section::make('')
                //         ->schema([
                //             Section::make('Morning Shift')
                //             ->schema([
                //                 TextInput::make('CheckinOne')
                //                     ->label('Check-in Time')
                //                     ->type('time')
                //                     ->required(fn (string $context) => $context === 'create'),
                                
                //                 TextInput::make('CheckoutOne')
                //                     ->label('Check-out Time')
                //                     ->type('time')
                //                     ->after('CheckinOne')
                //                     ->required(fn (string $context) => $context === 'create'),
        
                //             ])->collapsible(true)->columns(2)->compact()->columnSpan(1),
                        
                //             Section::make('Afternoon Shift')
                //             ->schema([
                //                 TextInput::make('CheckinTwo')
                //                     ->label('Check-in Time')
                //                     ->type('time')
                //                     ->required(fn (string $context) => $context === 'create'),
                                
                //                 TextInput::make('CheckoutTwo')
                //                     ->label('Check-out Time')
                //                     ->type('time')
                //                     ->after('CheckinTwo')
                //                     ->required(fn (string $context) => $context === 'create'),
                //             ])->collapsible(true)->columns(2)->compact()->columnSpan(1),
        
                //         ])->columns(2)->compact(),
                //     ])
                //     ->action(function ($record, $data) {
                //         // Create a new WorkSchedule
                //         $schedule = WorkSched::create([
                //         'ScheduleName' => $data['ScheduleName'],
                //         'RegularHours' => $data['RegularHours'],
                //         'monday' => $data['monday'] ?? false,
                //         'tuesday' => $data['tuesday'] ?? false,
                //         'wednesday' => $data['wednesday'] ?? false,
                //         'thursday' => $data['thursday'] ?? false,
                //         'friday' => $data['friday'] ?? false,
                //         'saturday' => $data['saturday'] ?? false,
                //         'sunday' => $data['sunday'] ?? false,
                //         'CheckinOne' => $data['CheckinOne'],
                //         'CheckoutOne' => $data['CheckoutOne'],
                //         'CheckinTwo' => $data['CheckinTwo'],
                //         'CheckoutTwo' => $data['CheckoutTwo'],
                //         ]);
    
                //         // Optionally, update the employee's current schedule in Employee model
                //         $record->update([
                //             'schedule_id' => $schedule->id,
                //         ]);

                //         Notification::make()
                //         ->title('Work Schedule Added')
                //         ->success()
                //         ->body('The work schedule has been successfully added.')
                //         ->send();
                //     }),

            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    
                    BulkAction::make('remove_from_project')
                        ->label('Remove from Project')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'project_id' => null,  
                                    'schedule_id' => null,  // Remove schedule assignment
                                    'status' => 'Available',
                                ]);
                            }

                            Notification::make()
                            ->title('Success')
                            ->body('Employees/Employee have been remove to the project project.')
                            ->success()
                            ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                ]),

                BulkAction::make('assign_to_schedule')
                        ->label('Assign to Work Schedule')
                        ->form([
                            Select::make('schedule_id')
                                ->label('Work Schedule')
                                ->relationship('schedule', 'ScheduleName')
                                ->required()
                        ])
                        ->action(function (array $data, Collection $records) {
                            $scheduleId = $data['schedule_id'];
        
                            foreach ($records as $record) {
                                $record->update(['schedule_id' => $scheduleId]);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
            ]);
    }
}
