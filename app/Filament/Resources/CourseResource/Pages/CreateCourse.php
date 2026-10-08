<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Services\GroupService;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    /** Holds group_ids stripped before Eloquent insert */
    protected array $pendingGroupIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingGroupIds = $data['group_ids'] ?? [];
        unset($data['group_ids']);
        return $data;
    }

    protected function afterCreate(): void
    {
        if (! empty($this->pendingGroupIds)) {
            app(GroupService::class)->syncContentGroups(
                'course',
                $this->record->id,
                $this->pendingGroupIds,
            );
        }
    }
}
