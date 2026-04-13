<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\ServiceProduct;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rule as ValidationRule;

final class InventoryItemFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'inventory-item-form';

    protected static string $view = 'filament.pages.inventory.inventory-item-form';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $editId = request()->query('id');
        if ($editId !== null && $editId !== '') {
            $item = ServiceProduct::query()
                ->where('company_id', $tenant->id)
                ->where('kind', 'product')
                ->findOrFail((int) $editId);

            $this->form->fill([
                'id' => $item->id,
                'name_ar' => $item->name_ar,
                'name_en' => $item->name_en,
                'code' => $item->code,
                'stock_quantity' => $item->stock_quantity,
                'unit_cost' => $item->unit_cost,
                'account_group_id' => $item->account_group_id,
            ]);
        } else {
            $this->form->fill([
                'stock_quantity' => 0,
                'unit_cost' => 0,
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return $form
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('اسم الصنف بالعربي')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('stock_quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                Forms\Components\TextInput::make('code')
                                    ->label('رمز الصنف')
                                    ->required()
                                    ->maxLength(100)
                                    ->rule(function () use ($tenant) {
                                        $rule = ValidationRule::unique('service_products', 'code')
                                            ->where('company_id', $tenant->id);
                                        if (! empty($this->data['id'])) {
                                            $rule->ignore($this->data['id']);
                                        }

                                        return $rule;
                                    }),
                                Forms\Components\Select::make('account_group_id')
                                    ->label('المجموعات')
                                    ->options(AccountGroup::indentedOptionsForCompany($tenant->id))
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم الصنف بالانجليزي')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('تكلفة الوحدة')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ]),
                    ]),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->form->validate();
        $data = $this->form->getState();

        $payload = [
            'company_id' => $tenant->id,
            'kind' => 'product',
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'code' => $data['code'],
            'stock_quantity' => (float) ($data['stock_quantity'] ?? 0),
            'unit_cost' => (float) ($data['unit_cost'] ?? 0),
            'sale_price' => (float) ($data['unit_cost'] ?? 0),
            'account_group_id' => $data['account_group_id'] ?? null,
        ];

        if (! empty($data['id'])) {
            ServiceProduct::query()
                ->where('company_id', $tenant->id)
                ->where('kind', 'product')
                ->whereKey((int) $data['id'])
                ->update($payload);
        } else {
            ServiceProduct::query()->create($payload);
        }

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(InventoryItemsListPage::getUrl());
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ')
                ->icon('heroicon-o-bookmark')
                ->submit('save'),
            Action::make('cancel')
                ->label('إلغاء')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action(fn () => $this->redirect(InventoryItemsListPage::getUrl())),
            Action::make('back')
                ->label('العودة للقائمة الرئيسية')
                ->icon('heroicon-o-x-mark')
                ->url(fn (): string => InventoryItemsListPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Company) {
            return 'اضافة صنف';
        }

        $editId = request()->query('id');

        return ($editId !== null && $editId !== '') ? 'تعديل صنف' : 'اضافة صنف';
    }
}
