<?php

namespace App\Filament\Resources\WorkSchedResource\Pages;

use App\Filament\Resources\WorkSchedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkSched extends EditRecord
{
    protected static ?string $title = 'Update Work Schedules';

    protected static string $resource = WorkSchedResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
