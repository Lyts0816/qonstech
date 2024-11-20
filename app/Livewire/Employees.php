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
use Illuminate\Support\Facades\DB;

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
                    ->formatStateUsing(fn($record) => $record->first_name . ' ' . ($record->middle_name ? $record->middle_name . ' ' : '') . $record->last_name)
                    ->sortable()
                    ->searchable(['first_name', 'middle_name', 'last_name']),

                Tables\Columns\TextColumn::make('position.PositionName'),

                TextColumn::make('province')
                    ->label('Province')
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        return DB::table('refprovince')
                            ->where('provCode', $state)
                            ->value('provDesc');
                    }),

                TextColumn::make('city')
                    ->label('City')
                    ->formatStateUsing(function ($state) {
                        return DB::table('refcitymun')
                            ->where('citymunCode', $state)
                            ->value('citymunDesc');
                    }),

                TextColumn::make('barangay')
                    ->label('Barangay')
                    ->formatStateUsing(function ($state) {
                        return DB::table('refbrgy')
                            ->where('brgyCode', $state)
                            ->value('brgyDesc');
                    }),

                TextColumn::make('street')->label('Street'),

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

            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),

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


                        $existingClerk = Employee::where('project_id', $projectId)
                            ->whereHas('position', function (Builder $query) {
                                $query->where('PositionName', 'Project Clerk');
                            })
                            ->first();


                        $clerkInSelection = $records->contains(function ($record) {
                            return $record->position->PositionName === 'Project Clerk';
                        });


                        if ($existingClerk && $clerkInSelection) {
                            Notification::make()
                                ->title('Error')
                                ->body('A project clerk is already assigned to this project.')
                                ->danger()
                                ->send();
                            return;
                        }


                        foreach ($records as $record) {
                            $record->update(['project_id' => $projectId, 'status' => 'Assigned']);
                        }


                        Notification::make()
                            ->title('Success')
                            ->body('Employees have been assigned to the project successfully.')
                            ->success()
                            ->send();
                    }),

            ]);
    }
}
