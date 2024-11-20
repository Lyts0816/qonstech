<?php

namespace App\Filament\Resources\WorkSchedResource\Pages;

use App\Filament\Resources\WorkSchedResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkSched extends CreateRecord
{
    protected static ?string $title = 'Create Work Schedules';
    protected static string $resource = WorkSchedResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
