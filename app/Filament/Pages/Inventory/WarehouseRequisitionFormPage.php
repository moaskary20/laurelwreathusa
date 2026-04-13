<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\Company;
use App\Models\ServiceProduct;
use App\Models\WarehouseRequisition;
use App\Models\WarehouseRequisitionLine;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class WarehouseRequisitionFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'warehouse-requisition-form';

    protected static string $view = 'filament.pages.inventory.warehouse-requisition-form';

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
            $req = WarehouseRequisition::query()
                ->where('company_id', $tenant->id)
                ->with('lines')
                ->findOrFail((int) $editId);

            $this->form->fill($this->requisitionToFormData($req));
        } else {
            $this->form->fill([
                'request_number' => WarehouseRequisition::nextRequestNumber($tenant->id),
                'request_date' => now()->toDateTimeString(),
                'lines' => [],
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function requisitionToFormData(WarehouseRequisition $req): array
    {
        return [
            'id' => $req->id,
            'request_number' => $req->request_number,
            'request_date' => $req->request_date?->toDateTimeString(),
            'lines' => $req->lines->map(fn (WarehouseRequisitionLine $line, int $index): array => [
                'service_product_id' => $line->service_product_id,
                'quantity' => $line->quantity,
                'sort_order' => $line->sort_order ?: $index,
            ])->values()->all(),
        ];
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
                        Forms\Components\DateTimePicker::make('request_date')
                            ->label('تاريخ طلب صرف مستودع')
                            ->required()
                            ->native(false)
                            ->seconds(false),
                        Forms\Components\TextInput::make('request_number')
                            ->label('رقم السند')
                            ->disabled()
                            ->dehydrated(true),
                    ]),
                Forms\Components\Repeater::make('lines')
                    ->label('بنود الطلب')
                    ->schema([
                        Forms\Components\Select::make('service_product_id')
                            ->label('اسم الصنف')
                            ->placeholder('اختر')
                            ->options(
                                ServiceProduct::query()
                                    ->where('company_id', $tenant->id)
                                    ->where('kind', 'product')
                                    ->orderBy('name_ar')
                                    ->pluck('name_ar', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->default(1)
                            ->required(),
                    ])
                    ->columns(2)
                    ->addActionLabel('اضافه طلب صرف +')
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->deletable(true),
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

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $row): bool => filled($row['service_product_id'] ?? null))
            ->values();

        DB::transaction(function () use ($tenant, $data, $lines): void {
            $payload = [
                'company_id' => $tenant->id,
                'request_date' => $data['request_date'],
            ];

            if (! empty($data['id'])) {
                $req = WarehouseRequisition::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $req->update($payload);
                $req->lines()->delete();
                $order = $req;
            } else {
                $payload['request_number'] = (int) ($data['request_number'] ?? WarehouseRequisition::nextRequestNumber($tenant->id));
                $payload['user_id'] = Auth::id();
                $order = WarehouseRequisition::query()->create($payload);
            }

            foreach ($lines as $index => $line) {
                $productId = (int) ($line['service_product_id'] ?? 0);
                $product = $productId
                    ? ServiceProduct::query()->where('company_id', $tenant->id)->find($productId)
                    : null;

                WarehouseRequisitionLine::query()->create([
                    'warehouse_requisition_id' => $order->id,
                    'service_product_id' => $productId ?: null,
                    'description' => $product?->name_ar,
                    'quantity' => (float) ($line['quantity'] ?? 1),
                    'cost_center_id' => null,
                    'sort_order' => $index,
                ]);
            }
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(WarehouseRequisitionPage::getUrl());
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
            Action::make('print')
                ->label('طباعه')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => $this->js('window.print()')),
            Action::make('back')
                ->label('العودة للقائمة الرئيسية')
                ->icon('heroicon-o-x-mark')
                ->url(fn (): string => WarehouseRequisitionPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'طلب صرف مستودع';
    }
}
