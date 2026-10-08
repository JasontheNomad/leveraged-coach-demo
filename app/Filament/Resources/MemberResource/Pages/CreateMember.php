<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = $data['full_name'];

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = new (static::getModel());
        $user->fill($data);                       // fillable fields only
        $user->role = $data['role'] ?? 'member';  // explicit — role is no longer fillable
        $user->save();

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
