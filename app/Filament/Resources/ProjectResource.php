<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\Pages\ShowEmployeesPage;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Filament\Resources\ProjectResource\RelationManagers\EmployeesRelationManager;
use App\Models\Project;
use Filament\Forms;
use App\Filament\Widgets\Employees;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Carbon\Carbon;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;
    protected static ?string $navigationGroup = "Projects/Assign";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                    Section::make('Project Information')
                    ->schema([
                        TextInput::make('ProjectName')
                        ->label('Project Name')
                        ->required(fn (string $context) => $context === 'create')
                        ->unique(ignoreRecord: true)
                        ->rules([
                            'regex:/^[a-zA-Z\s]*$/', // Ensures no digits are present
                            'min:3',            // Ensures the project name is at least 3 characters long
                            'max:50'            // Ensures the project name is no more than 50 characters long
                        ])
                        ->reactive()              // This triggers validation on each input change
                        ->debounce(500)           // Debounce to avoid rapid validation (500ms delay)
                        ->disabled(fn ($get, $context) => $context === 'edit' && $get('status') !== 'Complete')
                        ->validationMessages([
                            'regex' => 'The project name must not contain any digits or special characters.',
                            'min' => 'The project name must be at least 3 characters long.',
                            'max' => 'The project name must not exceed 50 characters.'
                        ]),

                        Select::make('Status')
                        ->label('Project Status')
                        ->options([
                            'On going' => 'On going',
                            'Incomplete' => 'Incomplete',
                            'Complete' => 'Complete',
                        ])
                        ->required()
                        ->reactive(),
                    

                    
                    Section::make('Location')
                    ->schema([
                        TextInput::make('PR_Province')
                        ->label('Province')
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->placeholder('Enter province')
                        ->rules(['required', 'string', 'max:255'])
                        ->disabled(fn ($get, $context) => $context === 'edit' && $get('status') !== 'Complete'),

                        TextInput::make('PR_City')
                        ->label('City/Municipality')
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->placeholder('Enter city')
                        ->rules(['required', 'string', 'max:255'])
                        ->disabled(fn ($get, $context) => $context === 'edit' && $get('status') !== 'Complete'),

                        TextInput::make('PR_Barangay')
                        ->label('Barangay')
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->placeholder('Enter barangay')
                        ->rules(['required', 'string', 'max:255'])
                        ->disabled(fn ($get, $context) => $context === 'edit' && $get('status') !== 'Complete'),

                        TextInput::make('PR_Street')
                        ->label('Street')
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->placeholder('Enter street')
                        ->rules(['required', 'string', 'max:255'])
                        ->disabled(fn ($get, $context) => $context === 'edit' && $get('status') !== 'Complete'),
                    ])
                    ->columns(4)
                    ->collapsible(true),

                    Section::make('Date')
                    ->schema([
                        DatePicker::make('StartDate')
                        ->label('Start Date')
                        ->disabled(fn ($get, $context) => $context === 'edit' && $get('status') !== 'Complete')
                        ->required(fn (string $context) => $context === 'create')
                        ->rules([
                            'date', // Ensures the value is a valid date
                        ])
                        ->minDate(fn (string $context) => $context === 'create' ? Carbon::today() : null) 
                        ->validationMessages([
                            'required' => 'The start date is required.',
                            'date' => 'The start date must be a valid date.',
                        ]),

                        DatePicker::make('EndDate')
                        ->label('End Date')
                        ->disabled(fn ($get, $context) => $context === 'edit' && $get('status') !== 'Complete')
                        ->required(fn (string $context) => $context === 'create')
                        ->after('StartDate')
                        ->rules([
                            'date', // Ensures the value is a valid date
                            'after:StartDate', // Ensures the end date is strictly after the start date
                        ])
                        ->validationMessages([
                            'required' => 'The end date is required.',
                            'date' => 'The end date must be a valid date.',
                            'after' => 'The end date must be after the start date.',
                        ]),
                    ])
                    ->columns(2)
                    ->collapsible(true),
                ])->collapsible(true)->collapsed(true),
            ]);
            
            
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ProjectName')->searchable(),
                TextColumn::make('Status')->label('Status'),
                TextColumn::make('PR_Province')
                ->label('Province')
                ->searchable(),
                TextColumn::make('PR_City')->label('City'),
                TextColumn::make('PR_Barangay')->label('Barangay'),
                TextColumn::make('PR_Street')->label('Street'),
                TextColumn::make('StartDate'),
                TextColumn::make('EndDate'),
            ])
            ->filters([
                
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                ->hidden(fn($record) => $record->trashed()),

                Tables\Actions\DeleteAction::make()->label('Deactivate')
                ->modalSubmitActionLabel('Deactivate')
                ->modalHeading('Deactivate Project')
                ->hidden(fn($record) => $record->trashed())
                ->successNotificationTitle('Project Deactivated'),

                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),

                
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    

    public static function getRelations(): array
    {
        return [
            EmployeesRelationManager::class
        ];
    }

    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            
        ];
    }


}
