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
                        
                        Select::make('PR_Province')
                            ->label('Province')
                            ->searchable()
                            ->required(fn (string $context) => $context === 'create' || $context === 'edit')
                            ->options([
                                'Abra' => 'Abra',
                                'Agusan del Norte' => 'Agusan del Norte',
                                'Agusan del Sur' => 'Agusan del Sur',
                                'Aklan' => 'Aklan',
                                'Albay' => 'Albay',
                                'Antique' => 'Antique',
                                'Apayao' => 'Apayao',
                                'Aurora' => 'Aurora',
                                'Basilan' => 'Basilan',
                                'Bataan' => 'Bataan',
                                'Batanes' => 'Batanes',
                                'Batangas' => 'Batangas',
                                'Benguet' => 'Benguet',
                                'Biliran' => 'Biliran',
                                'Bohol' => 'Bohol',
                                'Bukidnon' => 'Bukidnon',
                                'Bulacan' => 'Bulacan',
                                'Cagayan' => 'Cagayan',
                                'Camarines Norte' => 'Camarines Norte',
                                'Camarines Sur' => 'Camarines Sur',
                                'Camiguin' => 'Camiguin',
                                'Capiz' => 'Capiz',
                                'Catanduanes' => 'Catanduanes',
                                'Cavite' => 'Cavite',
                                'Cebu' => 'Cebu',
                                'Compostela Valley' => 'Compostela Valley',
                                'Cotabato' => 'Cotabato',
                                'Davao del Norte' => 'Davao del Norte',
                                'Davao del Sur' => 'Davao del Sur',
                                'Davao Occidental' => 'Davao Occidental',
                                'Davao Oriental' => 'Davao Oriental',
                                'Dinagat Islands' => 'Dinagat Islands',
                                'Eastern Samar' => 'Eastern Samar',
                                'Guimaras' => 'Guimaras',
                                'Ifugao' => 'Ifugao',
                                'Ilocos Norte' => 'Ilocos Norte',
                                'Ilocos Sur' => 'Ilocos Sur',
                                'Iloilo' => 'Iloilo',
                                'Isabela' => 'Isabela',
                                'Kalinga' => 'Kalinga',
                                'La Union' => 'La Union',
                                'Laguna' => 'Laguna',
                                'Lanao del Norte' => 'Lanao del Norte',
                                'Lanao del Sur' => 'Lanao del Sur',
                                'Leyte' => 'Leyte',
                                'Maguindanao' => 'Maguindanao',
                                'Marinduque' => 'Marinduque',
                                'Masbate' => 'Masbate',
                                'Misamis Occidental' => 'Misamis Occidental',
                                'Misamis Oriental' => 'Misamis Oriental',
                                'Mountain Province' => 'Mountain Province',
                                'Negros Occidental' => 'Negros Occidental',
                                'Negros Oriental' => 'Negros Oriental',
                                'Northern Samar' => 'Northern Samar',
                                'Nueva Ecija' => 'Nueva Ecija',
                                'Nueva Vizcaya' => 'Nueva Vizcaya',
                                'Occidental Mindoro' => 'Occidental Mindoro',
                                'Oriental Mindoro' => 'Oriental Mindoro',
                                'Palawan' => 'Palawan',
                                'Pampanga' => 'Pampanga',
                                'Pangasinan' => 'Pangasinan',
                                'Quezon' => 'Quezon',
                                'Quirino' => 'Quirino',
                                'Rizal' => 'Rizal',
                                'Romblon' => 'Romblon',
                                'Samar' => 'Samar',
                                'Sarangani' => 'Sarangani',
                                'Siquijor' => 'Siquijor',
                                'Sorsogon' => 'Sorsogon',
                                'South Cotabato' => 'South Cotabato',
                                'Southern Leyte' => 'Southern Leyte',
                                'Sultan Kudarat' => 'Sultan Kudarat',
                                'Sulu' => 'Sulu',
                                'Surigao del Norte' => 'Surigao del Norte',
                                'Surigao del Sur' => 'Surigao del Sur',
                                'Tarlac' => 'Tarlac',
                                'Tawi-Tawi' => 'Tawi-Tawi',
                                'Zambales' => 'Zambales',
                                'Zamboanga del Norte' => 'Zamboanga del Norte',
                                'Zamboanga del Sur' => 'Zamboanga del Sur',
                                'Zamboanga Sibugay' => 'Zamboanga Sibugay',
                            ])
                            ->placeholder('Select a province')
                            ->rules(['required', 'string', 'max:255'])
                            ->disabled(fn ($get, $context) => $context === 'edit' && $get('status') !== 'Complete'),

                            Select::make('PR_City')
                            ->label('City/Municipality')
                            ->searchable()
                            ->required(fn (string $context) => $context === 'create' || $context === 'edit')
                            ->options([
                                'Alaminos' => 'Alaminos',
                                'Angeles' => 'Angeles',
                                'Antipolo' => 'Antipolo',
                                'Bacolod' => 'Bacolod',
                                'Baguio' => 'Baguio',
                                'Bais' => 'Bais',
                                'Balanga' => 'Balanga',
                                'Banaue' => 'Banaue',
                                'Basilan' => 'Basilan',
                                'Batangas' => 'Batangas',
                                'Bayawan' => 'Bayawan',
                                'Bislig' => 'Bislig',
                                'Bogo' => 'Bogo',
                                'Butuan' => 'Butuan',
                                'Cabanatuan' => 'Cabanatuan',
                                'Cagayan de Oro' => 'Cagayan de Oro',
                                'Caloocan' => 'Caloocan',
                                'Cebu City' => 'Cebu City',
                                'Cotabato City' => 'Cotabato City',
                                'Dagupan' => 'Dagupan',
                                'Davao City' => 'Davao City',
                                'Digos' => 'Digos',
                                'Dipolog' => 'Dipolog',
                                'Iloilo City' => 'Iloilo City',
                                'Iriga' => 'Iriga',
                                'Kalibo' => 'Kalibo',
                                'Las Piñas' => 'Las Piñas',
                                'Lipa' => 'Lipa',
                                'Makati' => 'Makati',
                                'Malabon' => 'Malabon',
                                'Mandaue' => 'Mandaue',
                                'Manila' => 'Manila',
                                'Marikina' => 'Marikina',
                                'Meycauayan' => 'Meycauayan',
                                'Muntinlupa' => 'Muntinlupa',
                                'Navotas' => 'Navotas',
                                'Olongapo' => 'Olongapo',
                                'Ormoc' => 'Ormoc',
                                'Pagadian' => 'Pagadian',
                                'Panabo' => 'Panabo',
                                'Pasay' => 'Pasay',
                                'Pasig' => 'Pasig',
                                'Puerto Princesa' => 'Puerto Princesa',
                                'Quezon City' => 'Quezon City',
                                'Tagbilaran' => 'Tagbilaran',
                                'Taguig' => 'Taguig',
                                'Tarlac City' => 'Tarlac City',
                                'Tuguegarao' => 'Tuguegarao',
                                'Zamboanga City' => 'Zamboanga City',
                                'Baguio City' => 'Baguio City',
                                'Talisay' => 'Talisay',
                                'Bacolod City' => 'Bacolod City',
                                'Cagayan De Oro City' => 'Cagayan De Oro City',
                                'Davao Del Sur' => 'Davao Del Sur',
                                'Dumaguete' => 'Dumaguete',
                                'Iligan City' => 'Iligan City',
                                'Tacloban' => 'Tacloban',
                                'Taguig City' => 'Taguig City',
                                'Zamboanga Del Sur' => 'Zamboanga Del Sur',
                                'San Fernando' => 'San Fernando',
                                'Meycauayan City' => 'Meycauayan City',
                                'Bataan' => 'Bataan',
                                'Cebu City' => 'Cebu City',
                                'Muntinlupa City' => 'Muntinlupa City',
                                'Pasig City' => 'Pasig City',
                                // Add more cities as needed
                            ])
                            ->placeholder('Select a city')
                            ->rules(['required', 'string', 'max:255'])
                            ->disabled(fn ($get, $context) => $context === 'edit' && $get('status') !== 'Complete'),
                        

                        Select::make('PR_Barangay')
                        ->label('Barangay')
                        ->options([])
                        ->required(fn (string $context) => $context === 'create' || 'edit')
                        ->rules(['required'])
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
