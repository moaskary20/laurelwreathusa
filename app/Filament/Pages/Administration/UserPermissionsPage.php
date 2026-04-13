<?php

namespace App\Filament\Pages\Administration;

use App\Models\Company;
use App\Models\User;
use App\Support\UserPermissionRegistry;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property Form $form
 */
final class UserPermissionsPage extends Page
{
    protected static ?string $slug = 'user-permissions-page';

    protected static string $view = 'filament.pages.administration.user-permissions';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $title = 'صلاحيات المستخدمين';

    protected static ?string $navigationLabel = 'صلاحيات المستخدمين';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 10;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /**
     * @var array{user_id: ?int, grants: array<string, list<string>>}
     */
    public array $data = [];

    public function mount(): void
    {
        $this->data = [
            'user_id' => null,
            'grants' => $this->emptyGrants(),
        ];
        $this->form->fill($this->data);
    }

    /**
     * @return array<string, list<string>>
     */
    protected function emptyGrants(): array
    {
        $grants = [];
        foreach (array_keys(UserPermissionRegistry::grouped()) as $k) {
            $grants[$k] = [];
        }

        return $grants;
    }

    public function form(Form $form): Form
    {
        $sections = [
            Forms\Components\Select::make('user_id')
                ->label('المستخدم')
                ->options(fn (): array => $this->userOptions())
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state): void {
                    $this->hydrateGrantsForUser($state !== null && $state !== '' ? (int) $state : null);
                })
                ->columnSpanFull(),
        ];

        foreach (UserPermissionRegistry::grouped() as $groupKey => $block) {
            $sections[] = Forms\Components\Section::make($block['label'])
                ->schema([
                    Forms\Components\CheckboxList::make('grants.'.$groupKey)
                        ->label('')
                        ->options($block['items'])
                        ->columns(3)
                        ->gridDirection('row')
                        ->bulkToggleable()
                        ->searchable()
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->columnSpanFull();
        }

        return $form->schema($sections)->columns(1);
    }

    /**
     * @return array<int, string>
     */
    protected function userOptions(): array
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return User::query()
            ->where('company_id', $tenant->id)
            ->orderBy('name_ar')
            ->get()
            ->mapWithKeys(fn (User $u): array => [$u->id => $u->name_ar.' ('.$u->username.')'])
            ->all();
    }

    protected function hydrateGrantsForUser(?int $userId): void
    {
        $grants = $this->emptyGrants();

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        if ($userId) {
            $user = User::query()
                ->where('company_id', $tenant->id)
                ->find($userId);

            if ($user) {
                $enabled = $user->permissions ?? [];
                if (! is_array($enabled)) {
                    $enabled = [];
                }
                $enabled = array_values(array_filter($enabled, 'is_string'));

                foreach (UserPermissionRegistry::grouped() as $groupKey => $block) {
                    $keys = array_keys($block['items']);
                    $grants[$groupKey] = array_values(array_intersect($enabled, $keys));
                }
            }
        }

        $this->data['grants'] = $grants;
        $this->data['user_id'] = $userId;
        $this->form->fill($this->data);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $userId = $data['user_id'] ?? null;

        if (! $userId) {
            Notification::make()
                ->danger()
                ->title('اختر مستخدماً أولاً')
                ->send();

            return;
        }

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $flat = [];
        foreach ($data['grants'] ?? [] as $items) {
            if (is_array($items)) {
                $flat = array_merge($flat, $items);
            }
        }
        $flat = array_values(array_unique($flat));

        $updated = User::query()
            ->where('company_id', $tenant->id)
            ->whereKey($userId)
            ->update(['permissions' => $flat]);

        if (! $updated) {
            Notification::make()
                ->danger()
                ->title('تعذر الحفظ')
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('تم حفظ الصلاحيات')
            ->send();

        $this->hydrateGrantsForUser((int) $userId);
    }

    public function cancel(): void
    {
        $this->redirect(Filament::getUrl());
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->statePath('data'),
            ),
        ];
    }

    public function getTitle(): string | Htmlable
    {
        return 'صلاحيات المستخدمين';
    }
}
