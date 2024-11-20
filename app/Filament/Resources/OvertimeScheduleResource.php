<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OvertimeScheduleResource\Pages;
use App\Filament\Resources\OvertimeScheduleResource\RelationManagers;
use App\Models\OvertimeSchedule;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

                        Forms\Components\TextInput::make('Reason')
                            ->label('Reason')
                            ->required(),
                        Forms\Components\Select::make('EmployeeID')
                            ->label('Employee')
                            ->options(Employee::all()->pluck('full_name', 'id'))
                            ->required()
                            ->preload()
                            ->searchable()
                            ->reactive(),
                        Forms\Components\DatePicker::make('Date')
                            ->required(),
                    ])
                    ->columns(2)
                    ->collapsible(true),
            ]);
    }

    protected function afterSave(Model $record, array $data): void
    {
        if ($data['Status'] === 'approved') {
            $attendance = Attendance::where('EmployeeID', $data['EmployeeID'])
                ->where('Date', $data['Date'])
                ->first();


            if ($attendance) {
                $attendance->update([
                    'Overtime_In' => $data['Checkin'],
                    'Overtime_Out' => $data['Checkout'],
                ]);
            } else {

                Attendance::create([
                    'EmployeeID' => $data['EmployeeID'],
                    'Date' => $data['Date'],
                    'Overtime_In' => $data['Checkin'],
                    'Overtime_Out' => $data['Checkout'],

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
                TextColumn::make('Date')
                    ->label('Date'),
            ])
            ->filters([])
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
            ->bulkActions([]);
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
