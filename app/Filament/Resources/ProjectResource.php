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
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use App\Livewire\BarangaySelect;
use Illuminate\Support\Facades\DB;

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
                            ->required(fn(string $context) => $context === 'create')
                            ->unique(ignoreRecord: true)
                            ->rules([
                                'regex:/^[a-zA-Z\s]*$/',
                                'min:3',
                                'max:50'
                            ])
                            ->reactive()
                            ->debounce(500)
                            ->disabled(fn($get, $context) => $context === 'edit' && $get('status') !== 'Complete')
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
                                Select::make('PR_Province')
                                    ->label('Province')
                                    ->searchable()
                                    ->preload()
                                    ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                                    ->options(function () {
                                        return DB::table('refprovince')
                                            ->pluck('provDesc', 'provCode');
                                    })
                                    ->placeholder('Select a Province')
                                    ->rules(['required', 'string', 'max:255'])
                                    ->reactive(),

                                Select::make('PR_City')
                                    ->label('City/Municipality')
                                    ->searchable()
                                    ->preload()
                                    ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                                    ->options(function ($get) {
                                        $provinceCode = $get('PR_Province');
                                        if (!$provinceCode) {
                                            return [];
                                        }

                                        return DB::table('refcitymun')
                                            ->where('provCode', $provinceCode)
                                            ->pluck('citymunDesc', 'citymunCode');
                                    })
                                    ->placeholder('Select a City')
                                    ->rules(['required', 'string', 'max:255'])
                                    ->reactive(),

                                Select::make('PR_Barangay')
                                    ->label('Barangay')
                                    ->searchable()
                                    ->preload()
                                    ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                                    ->options(function ($get) {
                                        $cityCode = $get('PR_City');
                                        if (!$cityCode) {
                                            return [];
                                        }

                                        return DB::table('refbrgy')
                                            ->where('citymunCode', $cityCode)
                                            ->pluck('brgyDesc', 'brgyCode');
                                    })
                                    ->placeholder('Select a Barangay')
                                    ->rules(['required', 'string', 'max:255']),


                                TextInput::make('PR_Street')
                                    ->label('Street')
                                    ->required(fn(string $context) => $context === 'create' || 'edit')
                                    ->placeholder('Enter Street')
                                    ->rules(['required', 'string', 'max:255'])
                                    ->disabled(fn($get, $context) => $context === 'edit' && $get('status') !== 'Complete'),
                            ])
                            ->columns(4)
                            ->collapsible(true),

                        Section::make('Date')
                            ->schema([
                                DatePicker::make('StartDate')
                                    ->label('Start Date')
                                    ->disabled(fn($get, $context) => $context === 'edit' && $get('status') !== 'Complete')
                                    ->required(fn(string $context) => $context === 'create')
                                    ->rules([
                                        'date',
                                    ])
                                    ->minDate(fn(string $context) => $context === 'create' ? Carbon::today() : null)
                                    ->validationMessages([
                                        'required' => 'The start date is required.',
                                        'date' => 'The start date must be a valid date.',
                                    ]),

                                DatePicker::make('EndDate')
                                    ->label('End Date')
                                    ->disabled(fn($get, $context) => $context === 'edit' && $get('status') !== 'Complete')
                                    ->required(fn(string $context) => $context === 'create')
                                    ->after('StartDate')
                                    ->rules([
                                        'date',
                                        'after:StartDate',
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
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        return DB::table('refprovince')
                            ->where('provCode', $state)
                            ->value('provDesc');
                    }),

                TextColumn::make('PR_City')
                    ->label('City')
                    ->formatStateUsing(function ($state) {
                        return DB::table('refcitymun')
                            ->where('citymunCode', $state)
                            ->value('citymunDesc');
                    }),

                TextColumn::make('PR_Barangay')
                    ->label('Barangay')
                    ->formatStateUsing(function ($state) {
                        return DB::table('refbrgy')
                            ->where('brgyCode', $state)
                            ->value('brgyDesc');
                    }),
                TextColumn::make('PR_Street')->label('Street'),
                TextColumn::make('StartDate'),
                TextColumn::make('EndDate'),
            ])
            ->filters([])
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
            ->bulkActions([]);
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
