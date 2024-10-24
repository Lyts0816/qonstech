<?php

namespace App\Livewire;

use Filament\Tables;
use Filament\Notifications\Notification;
use App\Models\Employee;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class Employees extends BaseWidget
{

    public function table(Table $table): Table
    {   
        return $table
            ->query(
                Employee::query()->where('status', 'Available')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                
                Tables\Columns\TextColumn::make('full_name') 
                ->label('Name')
                ->formatStateUsing(fn ($record) => $record->first_name . ' ' . ($record->middle_name ? $record->middle_name . ' ' : '') . $record->last_name)
                ->sortable()
                ->searchable(['first_name', 'middle_name', 'last_name']),
                
                Tables\Columns\TextColumn::make('position.PositionName'),

                TextColumn::make('full_address')
                ->label('Address')
                ->formatStateUsing(fn ($record) => 
                    trim(
                        $record->street . ', ' . 
                        ($record->barangay ? $record->barangay . ', ' : '') . 
                        $record->city . ', ' . 
                        $record->province
                    )
                ),

                Tables\Columns\TextColumn::make('status')
            ])
            ->filters([

                SelectFilter::make('position_id')
                ->label('Filter by Position')
                ->relationship('position', 'PositionName')
                ->searchable()
                ->multiple()
                ->preload(),

                SelectFilter::make('City')
                ->label('Filter Employee by city')
                ->options(
                    \App\Models\Employee::query()
                        ->pluck('city', 'city')
                        ->toArray()
                )
                ->searchable()
                ->multiple()
                ->preload(),

                SelectFilter::make('Province')
                ->label('Filter Employee by Province')
                ->options(
                    \App\Models\Employee::query()
                        ->pluck('province', 'province')
                        ->toArray()
                )
                ->searchable()
                ->multiple()
                ->preload(),

            ])//end of filter

            ->actions([
                // Tables\Actions\EditAction::make(),
            ])


            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    
                ]),

                BulkAction::make('assign_to_project')
                ->label('Assign to Project')
                ->form([
                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'ProjectName')
                        ->required()
                ])
                ->deselectRecordsAfterCompletion()
                ->requiresConfirmation()
                ->action(function (array $data, Collection $records) {
                    $projectId = $data['project_id'];

                    // Find if a Project Clerk already exists in the selected project
                    $existingClerk = Employee::where('project_id', $projectId)
                        ->whereHas('position', function (Builder $query) {
                            $query->where('PositionName', 'Project Clerk');
                        })
                        ->first();

                    // Check if any of the selected records have a position of Project Clerk
                    $clerkInSelection = $records->contains(function ($record) {
                        return $record->position->PositionName === 'Project Clerk';
                    });

                    // If a clerk exists and there's a clerk in the selection, show an error
                    if ($existingClerk && $clerkInSelection) {
                        Notification::make()
                            ->title('Error')
                            ->body('A project clerk is already assigned to this project.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Assign project to the selected employees
                    foreach ($records as $record) {
                        $record->update(['project_id' => $projectId, 'status' => 'Assigned']);
                    }

                    // Notify success
                    Notification::make()
                        ->title('Success')
                        ->body('Employees have been assigned to the project successfully.')
                        ->success()
                        ->send();
                }),
                
            ]);
    }



}
