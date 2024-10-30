<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use App\Models\Employee;
use Illuminate\Validation\Rule;
use Dompdf\FrameDecorator\Text;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-user';

    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('EmployeeID')
                    ->label('Employee')
                    ->options(Employee::whereHas('position', function ($query) {
                        $query->whereIn('PositionName', [
                            'Project Clerk', 
                            'Human Resource', 
                            'Admin Vice President', 
                            'Finance Vice President'
                        ]);
                    })->get()->mapWithKeys(function ($employee) {
                        return [$employee->id => $employee->full_name . ' (' . $employee->position->PositionName . ')'];
                    }))
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $employee = Employee::find($state);
                        if ($employee) {
                            // Update the text input with the selected employee's full name
                            $set('name', $employee->full_name);

                            $set('role', $employee->position->PositionName);
                        }
                    }),


                    TextInput::make('name')
                    ->label('User Name')
                    ->required(fn(string $context) => $context === 'create')
                    ->string()
                    ->rules([
                        'regex:/^[a-zA-Z\s]*$/', 
                        'min:3',            
                        'max:30'            
                    ])
                    ->validationMessages([
                        'regex' => 'The user name must not contain any digits or special characters.',
                        'min' => 'The name must be at least 3 characters long.',
                        'max' => 'The name must not exceed 30 characters.'
                    ])->readOnly(fn($get) => $get('EmployeeID') !== null),
                
                TextInput::make('email')
                    ->label('Email')
                    ->required(fn(string $context) => $context === 'create')
                    ->unique(ignoreRecord: true)
                    ->email()
                    ->placeholder('Example@gmail.com')
                    ->rules([
                        'max:30' // Ensures the email is no more than 50 characters long
                    ])
                    ->validationMessages([
                        'email' => 'The email must be a valid email address.',
                        'unique' => 'The email has already been taken.',
                        'max' => 'The email must not exceed 30 characters.'
                    ]),

                    Select::make('role') // Field name
                    ->label('Role')
                    ->required(fn(string $context) => $context === 'create')
                    ->options([
                        'Project Clerk' => 'Project Clerk',
                        'Human Resource' => 'Human Resource',
                        'Admin Vice President' => 'Admin Vice President',
                        'Finance Vice President' => 'Finance Vice President',
                    ])
                    ->rules([
                        Rule::unique('users', 'role')->where(function ($query) {
                            return $query->whereIn('role', ['Human Resource', 'Admin Vice President', 'Finance Vice President']);
                        })->whereNot('id', request()->route('record'))
                    ])
                    ->validationMessages([
                        'unique' => 'Only one user with the Human Resource, Admin Vice President, or Finance Vice President role can be created.',
                    ]), 

                    Fieldset::make('Password')
                        ->schema([
                            TextInput::make('password')
                                ->required(fn(string $context) => $context === 'create')
                                ->visible(fn(string $context) => $context === 'create') // Only show on create
                                ->password()
                                ->revealable(true)
                                ->confirmed()
                                ->placeholder('Password')
                                ->helperText('Password must be 8-20 characters, include at least one uppercase letter, one lowercase letter, one number, and one symbol.')
                                ->rule(Password::default()->letters()->mixedCase()->numbers()->symbols())
                                ->maxLength(20),

                            TextInput::make('password_confirmation')
                                ->label('Confirm Password')
                                ->revealable(true)
                                ->required(fn(string $context) => $context === 'create')
                                ->visible(fn(string $context) => $context === 'create')
                                ->password()
                                ->placeholder('Confirm Password')
                                ->maxLength(20),
                        ])->columns(2)->visibleOn(['create']),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ->query(User::with('roles')) // Eager load the role relationship
            ->columns([
                TextColumn::make('EmployeeID')
                ->hidden(),
                    
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('role')
                    ->searchable(), // Allow searching by role name
                    
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                ->hidden(fn($record) => $record->trashed()),

                Tables\Actions\DeleteAction::make()->label('Deactivate')
                ->modalSubmitActionLabel('Deactivate')
                ->modalHeading('Deactivate User')
                ->hidden(fn($record) => $record->trashed())
                ->successNotificationTitle('User Deactivated'),

                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                    
                //     Tables\Actions\ForceDeleteBulkAction::make(),
                //     Tables\Actions\RestoreBulkAction::make(),
                // ]),
            ])
            ->defaultSort('EmployeeID', 'desc');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
