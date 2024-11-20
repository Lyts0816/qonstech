<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkSched;
use Faker\Provider\ar_EG\Text;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Relationship;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpParser\Node\Stmt\Label;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-c-user-group';

    protected static ?string $navigationGroup = "Employee Details";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Name')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->rules('regex:/^[^\d]*$/')
                            ->maxLength(30),

                        TextInput::make('middle_name')
                            ->label('Middle Name')
                            ->rules('regex:/^[^\d]*$/')
                            ->maxLength(30),

                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->rules('regex:/^[^\d]*$/')
                            ->maxLength(30),

                    ])->columns(3)->collapsible(true),

                Section::make('Address')
                    ->schema([
                        Select::make('province')
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

                        Select::make('city')
                            ->label('City/Municipality')
                            ->searchable()
                            ->preload()
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options(function ($get) {
                                $provinceCode = $get('province');
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

                        Select::make('barangay')
                            ->label('Barangay')
                            ->searchable()
                            ->preload()
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options(function ($get) {
                                $cityCode = $get('city');
                                if (!$cityCode) {
                                    return [];
                                }

                                return DB::table('refbrgy')
                                    ->where('citymunCode', $cityCode)
                                    ->pluck('brgyDesc', 'brgyCode');
                            })
                            ->placeholder('Select a Barangay')
                            ->rules(['required', 'string', 'max:255']),


                        TextInput::make('street')
                            ->label('Street')
                            ->required(fn(string $context) => $context === 'create' || 'edit')
                            ->placeholder('Enter Street')
                            ->rules(['required', 'string', 'max:255'])
                            ->disabled(fn($get, $context) => $context === 'edit' && $get('status') !== 'Complete'),
                    ])->columns(4)->collapsible(true),

                Section::make(heading: 'Employment Details')
                    ->schema([
                        Select::make('position_id')
                            ->label('Position')
                            ->options(
                                Position::query()
                                    ->pluck('PositionName', 'id')
                                    ->toArray()
                            )
                            ->required(fn(string $context) => $context === 'create' || 'edit')
                            ->afterStateUpdated(function ($state, callable $set) {
                                $position = Position::find($state);
                                if ($position && in_array($position->PositionName, ['Human Resource', 'Vice President'])) {
                                    $set('status', 'Office');
                                } else {
                                    $set('status', 'Available');
                                }
                            }),

                    ])->columns(4)->collapsible(true)->live(),

                Section::make('Contact Number/Status')
                    ->schema([

                        TextInput::make('contact_number')
                            ->label('Contact Number')
                            ->required(fn(string $context) => $context === 'create' || 'edit')
                            ->unique(ignoreRecord: true)
                            ->rules('regex:/^[\d]*$/')
                            ->maxLength(11)
                            ->minLength(11),

                        Select::make('employment_type')
                            ->label('Employment Type')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options([
                                'Regular' => 'Regular',
                                'Contractual' => 'Contractual',
                            ])
                            ->native(false)
                            ->live(),

                        Select::make('assignment')
                            ->label('Assignment')
                            ->required(fn(string $context) => $context === 'create' || 'edit')
                            ->options([
                                'Main Office' => 'Main Office',
                                'Project Based' => 'Project Based',
                            ])->native(false),

                        Select::make('status')
                            ->label('Status')
                            ->required(fn(string $context) => $context === 'create' || 'edit')
                            ->options([
                                'Office' => 'Office',
                                'Assigned' => 'Assigned',
                                'Available' => 'Available',
                            ])->default('Available'),

                    ])->columns(3)->collapsible(true),


                Section::make(heading: 'Other Details')
                    ->schema([
                        TextInput::make('TaxIdentificationNumber')
                            ->label('Tax ID Number')

                            ->unique(ignoreRecord: true)
                            ->regex('/^[0-9]{9}$/')
                            ->numeric()
                            ->placeholder('Enter 9-digit Tax ID')
                            ->maxLength(9)
                            ->minLength(9)
                            ->validationAttribute('Tax ID Number'),

                        TextInput::make('SSSNumber')
                            ->label('SSS Number')

                            ->unique(ignoreRecord: true)
                            ->regex('/^[0-9]{10}$/')
                            ->numeric()
                            ->placeholder('Enter 10-digit SSS Number')
                            ->maxLength(10)
                            ->minLength(10)
                            ->validationAttribute('SSS Number'),

                        TextInput::make('PhilHealthNumber')
                            ->label('PhilHealth Number')

                            ->unique(ignoreRecord: true)
                            ->regex('/^[0-9]{12}$/')
                            ->numeric()
                            ->placeholder('Enter 12-digit PhilHealth Number')
                            ->maxLength(12)
                            ->minLength(12)
                            ->validationAttribute('PhilHealth Number'),

                        TextInput::make('PagibigNumber')
                            ->label('Pagibig Number')

                            ->unique(ignoreRecord: true)
                            ->regex('/^[0-9]{12}$/')
                            ->placeholder('Enter 12-digit Pag-IBIG Number')
                            ->numeric()
                            ->maxLength(12)
                            ->minLength(12)
                            ->validationAttribute('Pagibig Number'),
                    ])
                    ->columns(4)
                    ->hidden(fn($get) => $get('employment_type') !== 'Regular'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Employee ID')->sortable(),
                TextColumn::make('full_name')
                    ->label('Name')
                    ->formatStateUsing(fn($record) => $record->first_name . ' ' . ($record->middle_name ? $record->middle_name . ' ' : '') . $record->last_name)
                    ->searchable(['first_name', 'middle_name', 'last_name']),

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
                TextColumn::make('TaxIdentificationNumber')->label('Tax Number'),
                TextColumn::make('SSSNumber')->label('SSS Number'),
                TextColumn::make('PhilHealthNumber')->label('PhilHealth Number'),
                TextColumn::make('PagibigNumber')->label('Pagibig Number'),
                TextColumn::make('assignment'),
                TextColumn::make('project.ProjectName'),
                TextColumn::make('position.PositionName'),
                TextColumn::make('schedule.ScheduleName'),
                TextColumn::make('contact_number'),
                TextColumn::make('employment_type'),
                TextColumn::make('status'),

            ])

            ->filters([
                SelectFilter::make('first_name')->label('Filter by Employee First Name')
                    ->options(
                        \App\Models\Employee::query()
                            ->pluck('first_name', 'first_name')
                            ->toArray()
                    )
                    ->searchable()->multiple()->preload(),

                SelectFilter::make('position_id')
                    ->label('Filter by Position')->relationship('position', 'PositionName')->searchable()->multiple()->preload(),

                SelectFilter::make('overtime_id')
                    ->label('Filter by Overtime')
                    ->relationship('overtime', 'Reason')
                    ->searchable()
                    ->multiple()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Assigned' => 'Assigned',
                        'Available' => 'Available',

                    ])
                    ->searchable()
                    ->preload(),

            ]) //end of filter

            ->actions([
                Tables\Actions\EditAction::make()->hidden(fn($record) => $record->trashed()),
                Tables\Actions\DeleteAction::make()->label('Deactivate')->modalSubmitActionLabel('Deactivate')->modalHeading('Deactivate Employee')->hidden(fn($record) => $record->trashed())->successNotificationTitle('Employee Deactivated'),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])

            ->bulkActions([

                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),


                BulkAction::make('assign_to_position')
                    ->label('Assign to Position')
                    ->form([
                        Select::make('position_id')
                            ->label('Position')
                            ->relationship('position', 'PositionName')
                            ->required()
                    ])
                    ->action(function (array $data, Collection $records) {
                        $projectId = $data['position_id'];

                        foreach ($records as $record) {
                            $record->update(['position_id' => $projectId]);
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation(),


                BulkAction::make('assign_to_overtime')
                    ->label('Assign to Overtime')
                    ->form([
                        Select::make('overtime_id')
                            ->label('Overtime')
                            ->relationship('overtime', 'Reason')
                            ->required()
                    ])
                    ->action(function (array $data, Collection $records) {
                        $projectId = $data['overtime_id'];

                        foreach ($records as $record) {
                            $record->update(['overtime_id' => $projectId]);
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation(),

                BulkAction::make('assign_to_worksched')
                    ->label('Assign to WorkSched')
                    ->form([
                        Select::make('schedule_id')
                            ->label('Schedules')
                            ->relationship('schedule', 'ScheduleName')
                            ->required()
                    ])
                    ->action(function (array $data, Collection $records) {
                        $schedid = $data['schedule_id'];

                        foreach ($records as $record) {
                            $record->update(['schedule_id' => $schedid]);
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
            'show-employees' => ProjectResource\Pages\ShowEmployeesPage::route('/show-employees'),
        ];
    }
}
