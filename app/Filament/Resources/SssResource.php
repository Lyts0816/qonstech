<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SssResource\Pages;
use App\Filament\Resources\SssResource\RelationManagers;
use App\Models\sss;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpParser\Node\Stmt\Label;

class SssResource extends Resource
{
    protected static ?string $model = sss::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = "Contribution";
    protected static ?string $title = 'SSS';
    protected static ?string $breadcrumb = "SSS";
    protected static ?string $navigationLabel = 'SSS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Minimum Salary Credit
                TextInput::make('MinSalary')
                    ->label('Minimum Salary Credit')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter minimum salary credit for this bracket'),

                // Maximum Salary Credit
                TextInput::make('MaxSalary')
                    ->label('Maximum Salary Credit')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter maximum salary credit for this bracket'),

                // Regular SS Contribution (Employer)
                TextInput::make('EmployerShare')
                    ->label('Employer Regular SS Contribution (PHP)')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter employer regular SS contribution'),

                // Regular SS Contribution (Employee)
                TextInput::make('EmployeeShare')
                    ->label('Employee Regular SS Contribution (PHP)')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter employee regular SS contribution'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('SSS ID')
                    ->searchable(),

                TextColumn::make('MinSalary')
                    ->searchable(),

                TextColumn::make('MaxSalary')
                    ->searchable(),

                TextColumn::make('EmployeeShare'),
                TextColumn::make('EmployerShare'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListSsses::route('/'),
            'create' => Pages\CreateSss::route('/create'),
            'edit' => Pages\EditSss::route('/{record}/edit'),
        ];
    }
}
