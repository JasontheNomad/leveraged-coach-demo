<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use App\Models\Announcement;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'manual';
        $data['icon'] = Announcement::iconForType('manual');

        // Normalize empty role array to null (means all roles)
        if (empty($data['visibility_roles'])) {
            $data['visibility_roles'] = null;
        }

        return $data;
    }
}
