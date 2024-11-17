<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OvertimeScheduleResource\Pages;
use App\Filament\Resources\OvertimeScheduleResource\RelationManagers;
use App\Models\OvertimeSchedule;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Model; // Import Model
use Illuminate\Support\Facades\DB; // For database transactions
use Faker\Provider\ar_EG\Text;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OvertimeScheduleResource extends Resource
{
    protected static ?string $model = OvertimeSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Overtime Schedule';
    protected static ?string $navigationGroup = "Overtime/Assign";

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Overtime Schedule Information')
                    ->schema([
                        // Reason Input Field
                        Forms\Components\TextInput::make('Reason')
                            ->label('Reason')
                            ->required(),

                        // Employee Select Field
                        Forms\Components\Select::make('EmployeeID')
                            ->label('Employee')
                            ->options(Employee::all()->pluck('full_name', 'id'))
                            ->required()
                            ->preload()
                            ->searchable()
                            ->reactive(),

                        // // Check-in Time Input Field
                        // Forms\Components\TextInput::make('Checkin')
                        //     ->label('Check-in Time')
                        //     ->type('time')
                        //     ->required(),

                        // // Check-out Time Input Field
                        // Forms\Components\TextInput::make('Checkout')
                        //     ->label('Check-out Time')
                        //     ->type('time')
                        //     ->after('Checkin')
                        //     ->required(),

                        // Date Picker
                        Forms\Components\DatePicker::make('Date')
                            ->required(),

                        // // Status Select Field
                        // Forms\Components\Select::make('Status')
                        //     ->label('Status')
                        //     ->options([
                        //         'approved' => 'Approved',
                        //         'pending' => 'Pending',
                        //         'rejected' => 'Rejected',
                        //     ])
                        //     ->required(),
                    ])
                    ->columns(2) // Set the layout to two columns for better UI alignment
                    ->collapsible(true), // Allow the section to collapse for better user experience
            ]);
    }
    
    protected function afterSave(Model $record, array $data): void
    {
        // dd($data['Status']);
        // Only process if the status is approved
        if ($data['Status'] === 'approved') {
            // Check for existing attendance record
            $attendance = Attendance::where('EmployeeID', $data['EmployeeID'])
                ->where('Date', $data['Date'])
                ->first();

            // If attendance exists, update Overtime In and Out
            if ($attendance) {
                $attendance->update([
                    'Overtime_In' => $data['Checkin'],
                    'Overtime_Out' => $data['Checkout'],
                ]);
            } else {
                // If it doesn't exist, create a new attendance record
                Attendance::create([
                    'EmployeeID' => $data['EmployeeID'],
                    'Date' => $data['Date'],
                    'Overtime_In' => $data['Checkin'],
                    'Overtime_Out' => $data['Checkout'],
                    // Include other necessary fields if any
                ]);
            }
        }
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                ->label('Employee')
                ->searchable(['employees.first_name', 'employees.middle_name', 'employees.last_name']),
            


                TextColumn::make('Reason')
                    ->label('Reason'),

                // TextColumn::make('Checkin')
                //     ->label('Check-in'),

                // TextColumn::make('Checkout')
                //     ->label('Check-out'),

                TextColumn::make('Date')
                    ->label('Date'),

                // TextColumn::make('Status')
                //     ->label('Status'),
                
            ])
            ->filters([
                // Add filters here if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->hidden(fn($record) => $record->trashed()),

                Tables\Actions\DeleteAction::make()->label('Deactivate')
                ->modalSubmitActionLabel('Deactivate')
                ->modalHeading('Deactivate Overtime')
                ->hidden(fn($record) => $record->trashed())
                ->successNotificationTitle('Overtime Deactivated'),

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
            'index' => Pages\ListOvertimeSchedules::route('/'),
            'create' => Pages\CreateOvertimeSchedule::route('/create'),
            'edit' => Pages\EditOvertimeSchedule::route('/{record}/edit'),
        ];
    }
}
