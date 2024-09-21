<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Pages\ShowAvailableEmployees;
use Filament\Forms;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                Tables\Columns\TextColumn::make('full_name') 
                ->label('Name')
                ->formatStateUsing(fn ($record) => $record->first_name . ' ' . ($record->middle_name ? $record->middle_name . ' ' : '') . $record->last_name)
                ->sortable()
                ->searchable(),

                Tables\Columns\TextColumn::make('position.PositionName'),

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
                ->url(ShowAvailableEmployees::getUrl())
                
            ])
            ->actions([

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    
                    BulkAction::make('remove_from_project')
                        ->label('Remove from Project')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'project_id' => null,  // Remove project assignment by setting project_id to null
                                    'status' => 'Available'  // Update status to Available
                                ]);
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                ]),
            ]);
    }
}