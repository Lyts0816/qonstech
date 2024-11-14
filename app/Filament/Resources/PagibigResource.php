<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PagibigResource\Pages;
use App\Filament\Resources\PagibigResource\RelationManagers;
use App\Models\pagibig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class PagibigResource extends Resource
{
    protected static ?string $model = pagibig::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "Contribution";

    protected static ?string $title = 'PAGIBIG';

    

    protected static ?string $breadcrumb = "PAGIBIG";

    protected static ?string $navigationLabel = 'PAGIBIG';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Minimum Salary
                TextInput::make('MinimumSalary')
                    ->label('Minimum Salary')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter minimum salary for this bracket'),
    
                // Maximum Salary
                TextInput::make('MaximumSalary')
                    ->label('Maximum Salary')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter maximum salary for this bracket'),
    
                // Employee Share Percentage
                TextInput::make('EmployeeRate')
                    ->label('Employee Contribution')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter employee share percentage for this bracket'),
    
                // Employer Share Percentage
                TextInput::make('EmployerRate')
                    ->label('Employer Contribution')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter employer share percentage for this bracket'),
    
            ]);
    }
    
    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             TextInput::make('MonthlySalary')
    //             ->required(fn (string $context) => $context === 'create')
    //             ->numeric()
    //             ->unique(ignoreRecord: true)
    //             ,

    //             TextInput::make('Rate')
    //             ->required(fn (string $context) => $context === 'create')
    //             ->numeric()
    //             ->unique(ignoreRecord: true)
    //             ,
    //         ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                ->label('PAGIBIG ID')
                ->searchable(),

                TextColumn::make('MinimumSalary')
                ->searchable(),

                TextColumn::make('MaximumSalary')
                ->searchable(),

                TextColumn::make('EmployeeRate')
                ->searchable(),

                TextColumn::make('EmployerRate')
                ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordUrl(function ($record) {
                return null;
            })
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([

            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPagibigs::route('/'),
            'create' => Pages\CreatePagibig::route('/create'),
            'edit' => Pages\EditPagibig::route('/{record}/edit'),
        ];
    }
}
