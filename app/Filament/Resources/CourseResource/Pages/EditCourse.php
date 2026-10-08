<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Services\GroupService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    /** Holds group_ids stripped before Eloquent update */
    protected array $pendingGroupIds = [];

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Pre-populate the CheckboxList with the course's current group assignments.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['group_ids'] = DB::table('content_groups')
            ->where('content_type', 'course')
            ->where('content_id', $this->record->id)
            ->pluck('group_id')
            ->toArray();

        return $data;
    }

    /**
     * Strip the virtual field before Eloquent save.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingGroupIds = $data['group_ids'] ?? [];
        unset($data['group_ids']);
        return $data;
    }

    /**
     * Sync content_groups after the record is saved.
     */
    protected function afterSave(): void
    {
        app(GroupService::class)->syncContentGroups(
            'course',
            $this->record->id,
            $this->pendingGroupIds,
        );
    }
}
