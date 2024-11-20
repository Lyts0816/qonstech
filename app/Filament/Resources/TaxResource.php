<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxResource\Pages;
use App\Filament\Resources\TaxResource\RelationManagers;
use App\Models\Tax;
use Dompdf\FrameDecorator\Text;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "Contribution";

    protected static ?string $title = 'TAX';

    protected static ?string $breadcrumb = "TAX";

    protected static ?string $navigationLabel = 'TAX';

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
                TextInput::make('compensation')
                    ->label('Compensation')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter base rate amount for this bracket'),

                TextInput::make('wth_tax')
                    ->label('wth_tax')
                    ->numeric()
                    ->required(fn(string $context) => $context === 'create')
                    ->placeholder('Enter base rate amount for this bracket'),

                // Contribution Rate
                TextInput::make('excess_percent')
                    ->label('Percentage')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter percentage(e.g., 5 for 5%)'),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('TAX ID')
                    ->searchable(),

                TextColumn::make('MinSalary')
                    ->searchable(),

                TextColumn::make('MaxSalary')
                    ->searchable(),

                TextColumn::make('compensation'),
                TextColumn::make('wth_tax'),
                TextColumn::make('excess_percent'),
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
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'edit' => Pages\EditTax::route('/{record}/edit'),
        ];
    }
}
