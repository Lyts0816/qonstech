<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhilhealthResource\Pages;
use App\Filament\Resources\PhilhealthResource\RelationManagers;
use App\Models\philhealth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class PhilhealthResource extends Resource
{
    protected static ?string $model = philhealth::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "Contribution";

    protected static ?string $title = 'PHILHEALTH';

    protected static ?string $breadcrumb = "PHILHEALTH";

    protected static ?string $navigationLabel = 'PHILHEALTH';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Minimum Salary
                TextInput::make('MinSalary')
                    ->label('Minimum Salary')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter minimum salary for this bracket'),

                // Maximum Salary
                TextInput::make('MaxSalary')
                    ->label('Maximum Salary')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter maximum salary for this bracket'),

                // Contribution Amount
                TextInput::make('ContributionAmount')
                    ->label('PhilHealth Contribution Amount (PHP)')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter contribution amount for this bracket'),

                // Contribution Rate
                TextInput::make('PremiumRate')
                    ->label('PhilHealth Contribution Rate (%)')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter contribution rate (e.g., 5 for 5%)'),

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

                TextColumn::make('PremiumRate'),
                TextColumn::make('ContributionAmount'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
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
            'index' => Pages\ListPhilhealths::route('/'),
            'create' => Pages\CreatePhilhealth::route('/create'),
            'edit' => Pages\EditPhilhealth::route('/{record}/edit'),
        ];
    }
}
