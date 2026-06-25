<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Company;
use App\Support\UserPermissionRegistry;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected static string $view = 'filament.resources.user-resource.pages.edit-user';

    protected static ?string $title = 'تعديل مستخدم';

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permission_grants'] = UserPermissionRegistry::splitIntoGrants($data['permissions'] ?? null);

        if (! empty($data['company_id'])) {
            $company = Company::query()->find($data['company_id']);
            $data['storage_quota_gb'] = UserResource::storageQuotaGbFromMb($company?->storage_quota_mb);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = UserResource::persistPermissionGrants($data);

        if (! empty($data['name_ar'])) {
            $data['name'] = $data['name_ar'];
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $state = $this->form->getState();
        UserResource::persistCompanyStorageQuota(
            $this->record->company_id ? (int) $this->record->company_id : null,
            $state['storage_quota_gb'] ?? null,
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
