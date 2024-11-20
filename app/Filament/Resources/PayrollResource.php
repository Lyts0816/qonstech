<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Filament\Resources\PayrollResource\RelationManagers;
use App\Models\Payroll;
use App\Models\WeekPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Project;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollExport;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PayrollResource extends Resource
{
	protected static ?string $model = Payroll::class;

	protected static ?string $navigationIcon = 'heroicon-c-bars-3-center-left';

	protected static ?string $title = 'PHILHEALTH';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Grid::make(2) // Create a two-column grid layout for the first two fields
					->schema([
						// Employee Status Select Field
						Select::make('EmployeeStatus')
							->label('Employee Status')
							->required(fn(string $context) => $context === 'create' || $context === 'edit')
							->options([
								'Regular' => 'Regular',
								'Contractual' => 'Contractual',
							])
							->default(request()->query('employee'))
							->reactive()
							->afterStateUpdated(function (callable $set, $state) {
								// Automatically set PayrollFrequency based on EmployeeStatus
								if ($state === 'Regular') {
									$set('PayrollFrequency', 'Kinsenas');
								} elseif ($state === 'Contractual') {
									$set('PayrollFrequency', 'Weekly');
								}
							}),

						// Assignment Select Field
						Select::make('assignment')
							->label('Assignment')
							->required(fn(string $context) => $context === 'create' || $context === 'edit')
							->options([
								'Main Office' => 'Kinsenas',
								'Project Based' => 'Project Based',
							])
							->native(false)
							->reactive()
							->afterStateUpdated(function (callable $set, $state) {
								// Clear project selection if assignment is not Project Based
								if ($state !== 'Project Based') {
									$set('ProjectID', null); // Reset the project selection
								}
							}),

						// Project Select Field - only shown if assignment is Project Based
						Select::make('ProjectID')
							->label('Project')
							->required(fn(string $context) => $context === 'create' || $context === 'edit')
							->options(function () {
								// Fetch projects from the database (assuming you have a Project model)
								return \App\Models\Project::pluck('ProjectName', 'id'); // Change 'name' to the actual field for project name
							})
							->hidden(fn($get) => $get('assignment') !== 'Project Based'), // Hide if not project based

					]),

				// Other fields...
				Select::make('PayrollFrequency')
					->label('Payroll Frequency')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options([
						'Kinsenas' => 'Kinsenas (Bi-monthly)',
						'Weekly' => 'Weekly',
					])
					->native(false)
					->reactive(),

				// PayrollDate Select Field
				Select::make('PayrollDate2')
					->label('Payroll Date')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options(function (callable $get) {
						$frequency = $get('PayrollFrequency');

						if ($frequency == 'Kinsenas') {
							return [
								'1st Kinsena' => '1st-15th',
								'2nd Kinsena' => '16th-End of the Month',
							];
						} elseif ($frequency == 'Weekly') {
							return [
								'Week 1' => 'Week 1',
								'Week 2' => 'Week 2',
								'Week 3' => 'Week 3',
								'Week 4' => 'Week 4',
							];
						}

						return [];
					})
					->reactive(),

				// Payroll Month Select Field
				Select::make('PayrollMonth')
					->label('Payroll Month')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options([
						'January' => 'January',
						'February' => 'February',
						'March' => 'March',
						'April' => 'April',
						'May' => 'May',
						'June' => 'June',
						'July' => 'July',
						'August' => 'August',
						'September' => 'September',
						'October' => 'October',
						'November' => 'November',
						'December' => 'December',
					])
					->native(false)
					->reactive(),

				// Payroll Year Select Field
				Select::make('PayrollYear')
					->label('Payroll Year')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options(function () {
						$currentYear = date('Y');
						$years = [];
						for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
							$years[$i] = $i;
						}
						return $years;
					})
					->native(false)
					->reactive(),

				// weekPeriodID Select Field
				Select::make('weekPeriodID')
					->label('Payroll Period')
					->required(fn(string $context) => $context === 'create' || $context === 'edit')
					->options(function (callable $get) {
						// Fetch selected values from other fields
						$month = $get('PayrollMonth');
						$frequency = $get('PayrollFrequency');
						$payrollDate = $get('PayrollDate2');
						$year = $get('PayrollYear');

						// Ensure that all necessary fields are filled before proceeding
						if ($month && $frequency && $payrollDate && $year) {
							try {
								// Convert month name to month number (e.g., 'January' to '01')
								$monthId = Carbon::createFromFormat('F', $month)->format('m');

								// Fetch WeekPeriod entries based on the selected criteria
								return WeekPeriod::where('Month', $monthId)
									->where('Category', $frequency)
									->where('Type', $payrollDate)
									->where('Year', $year)
									->get()
									->mapWithKeys(function ($period) {
										return [
											$period->id => $period->StartDate . ' - ' . $period->EndDate,
										];
									});
							} catch (\Exception $e) {
								// In case there is an issue with parsing the date or any other issue
								return [];
							}
						}
						return [];
					})
					->reactive() // Make this field reactive to other fields
					->placeholder('Select the payroll period'),

			]);
	}


	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('EmployeeStatus')
					->label('Employee Type')
					->searchable()
					->sortable(),

				Tables\Columns\TextColumn::make('assignment')
					->label('Assignment')
					->searchable()
					->sortable(),

				Tables\Columns\TextColumn::make('project.ProjectName') 
					->label('Project')
					->searchable()
					->sortable(),

				Tables\Columns\TextColumn::make('PayrollYear')
					->searchable()
					->Label('Payroll Year'),

				Tables\Columns\TextColumn::make('PayrollMonth')
					->searchable()
					->Label('Payroll Month'),

				Tables\Columns\TextColumn::make('PayrollFrequency')
					->Label('Payroll Frequency'),

				Tables\Columns\TextColumn::make('PayrollDate2')
					->label('Payroll Period')
					->searchable()
					->sortable(),

			])
			->defaultSort('created_at', 'desc')
			->recordUrl(function ($record) {
				return null;
			})
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
			], layout: FiltersLayout::AboveContent)
			->actions([
				Tables\Actions\Action::make('viewPayroll')
					->hidden(fn($record) => $record->trashed())
					->label(fn() => in_array(Auth::user()->role, [User::ROLE_FIVP, User::ROLE_ADMINUSER]) ? 'View' : 'Generate Payroll Summary')
					->icon('heroicon-o-calculator')
					->color('success')
					->url(fn($record) => route('payroll-report', $record->toArray())) // Pass all attributes from the record as an array
					->openUrlInNewTab()
					->action(function () {
						
					}),

				Tables\Actions\DeleteAction::make()->label('Archive')
					->modalSubmitActionLabel('Archived')
					->modalHeading('Archived Payroll')
					->hidden(fn($record) => $record->trashed())
					->successNotificationTitle('Payroll Archived'),

				Tables\Actions\RestoreAction::make(),
				
			])
			->bulkActions([
			]);
	}

	/**
	 * Example payroll calculation method.
	 *
	 * @param Payroll|array $data
	 * @return float
	 */
	protected static function calculateNetPay($data)
	{
		if ($data instanceof Payroll) {
			return $data->GrossPay - $data->TotalDeductions;
		} elseif (is_array($data)) {
			$grossPay = isset($data['GrossPay']) ? floatval($data['GrossPay']) : 0;
			$totalDeductions = isset($data['TotalDeductions']) ? floatval($data['TotalDeductions']) : 0;
			return $grossPay - $totalDeductions;
		}

		return 0;
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
			'index' => Pages\ListPayrolls::route('/'),
			'create' => Pages\CreatePayroll::route('/create'),
			'edit' => Pages\EditPayroll::route('/{record}/edit'),
		];
	}
}
