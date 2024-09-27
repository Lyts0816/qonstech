<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Resources\Resource;
use Filament\Tables;
use App\Models\Employee;
use App\Models\Project;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpParser\Node\Stmt\Label;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $title = 'Attendance';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                ->label('Employee Name'),
                
                TextColumn::make('employee.project.ProjectName')
                ->Label('Project Name'),

                TextColumn::make('Checkin_One')
                ->label('Morning Check-in')
                ,

                TextColumn::make('Checkout_One')
                ->label('Morning Checkout')
                ,

                TextColumn::make('Checkin_Two')
                ->label('Afternoon Check-in')
                ,

                TextColumn::make('Checkout_Two')
                ->label('Afternoon Checkout')
                ,

                TextColumn::make('Date')
                ->label('Date')
                ->sortable(),

                TextColumn::make('Total_Hours')
                ->label('Total Hours')
                ->sortable()
            
            ])
            ->filters([
                SelectFilter::make('project_id')
                ->label('Select Project')
                ->options(Project::all()->pluck('ProjectName', 'id'))
                
                ->query(function (Builder $query, array $data) {
                    if (empty($data['value'])) {
                        
                        return $query;
                    }
                    return $query->whereHas('employee.project', function (Builder $query) use ($data) {
                        $query->where('id', $data['value']);
                    });
                }),


                // Filter::make('date')
                // ->label('Date')
                // ->form([
                    
                //     Forms\Components\DatePicker::make('date_from')
                //         ->label('From Date'),
                //     Forms\Components\DatePicker::make('date_to')
                //         ->label('To Date'),
                // ])
                // ->query(function (Builder $query, array $data) {
                //     if (!empty($data['date_from'])) {
                //         $query->where('Date', '>=', $data['date_from']);
                //     }
                //     if (!empty($data['date_to'])) {
                //         $query->where('Date', '<=', $data['date_to']);
                //     }
                //     return $query;
                // })
            ], layout: FiltersLayout::AboveContent)


            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([

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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
