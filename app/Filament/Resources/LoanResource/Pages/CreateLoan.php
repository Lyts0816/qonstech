<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use App\Models\LoanDtl;
use App\Models\WeekPeriod;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;
    protected static ?string $title = 'Add Loan';
    protected function handleRecordCreation(array $data): \App\Models\Loan
    {
        
        $request = parent::handleRecordCreation($data);
        $loanID = $request->id; 
        $periodID = $data['PeriodID']; 
        $loanAmount = $data['LoanAmount'];
        $KinsenaDeduction = $data['KinsenaDeduction']; 
        $noOfPayments = isset($data['NumberOfPayments']) ? (int) $data['NumberOfPayments'] * 2 : 0;

        if (!$loanID || !$periodID || !$loanAmount || !$noOfPayments) {
            return $request;  
        }
        $isPaid = false;
        $isRenewed = false;
        for ($i = 0; $i < $noOfPayments; $i++) {
            $currentPeriod = WeekPeriod::where('id', $periodID)
                ->where('Category', 'Kinsenas') 
                ->first();
            if (!$currentPeriod) {
                break;
            }
            $sequence = $i + 1; 
            DB::table('loandtl')->insert([
                'loanid' => $loanID,
                'sequence' => $sequence,
                'tran_date' => $currentPeriod->StartDate, 
                'periodid' => $periodID,
                'amount' => $KinsenaDeduction, 
                'ispaid' => (int) $isPaid,
                'isrenewed' => (int) $isRenewed,
            ]);
            $nextPeriod = WeekPeriod::where('StartDate', '>', $currentPeriod->StartDate)
                ->where('Category', 'Kinsenas') 
                ->orderBy('StartDate', 'asc')
                ->first();
            if (!$nextPeriod) {
                break;
            }
            $periodID = $nextPeriod->id;
        }
        return $request;
    }
}
