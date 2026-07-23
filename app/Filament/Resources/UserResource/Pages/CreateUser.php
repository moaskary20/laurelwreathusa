<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static string $view = 'filament.resources.user-resource.pages.create-user';

    protected static ?string $title = 'إضافة مستخدم';

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = UserResource::persistPermissionGrants($data);
        $data['company_id'] ??= Filament::getTenant()?->getKey();
        if (! empty($data['name_ar'])) {
            $data['name'] = $data['name_ar'];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getState();
        UserResource::persistCompanyStorageQuota(
            $this->record->company_id ? (int) $this->record->company_id : null,
            $state['storage_quota_gb'] ?? null,
        );
    }

    protected function getRedirectUrl(): string
    {
        $companyId = $this->record->company_id ? (int) $this->record->company_id : null;
        $tenantId = Filament::getTenant()?->getKey();

        if ($companyId && (int) $tenantId !== $companyId) {
            return UserResource::getUrl('index', ['tenant' => $companyId]);
        }

        return $this->getResource()::getUrl('index');
    }
}
