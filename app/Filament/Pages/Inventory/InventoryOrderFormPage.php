<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderLine;
use App\Models\ServiceProduct;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormInlineAction;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class InventoryOrderFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'inventory-order-form';

    protected static string $view = 'filament.pages.inventory.inventory-order-form';

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
            $order = InventoryOrder::query()
                ->where('company_id', $tenant->id)
                ->with(['lines', 'customer'])
                ->findOrFail((int) $editId);

            $this->form->fill($this->orderToFormData($order));
        } else {
            $this->form->fill([
                'order_number' => InventoryOrder::nextOrderNumber($tenant->id),
                'order_date' => now()->toDateTimeString(),
                'lines' => [],
                'total_value' => 0,
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderToFormData(InventoryOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_id' => $order->customer_id,
            'order_date' => $order->order_date?->toDateTimeString(),
            'total_value' => $order->total_value,
            'lines' => $order->lines->map(fn (InventoryOrderLine $line, int $index): array => [
                'service_product_id' => $line->service_product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'line_total' => $line->line_total,
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
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('اسم العميل')
                            ->placeholder('اختيار عميل')
                            ->required()
                            ->options(
                                Customer::query()
                                    ->where('company_id', $tenant->id)
                                    ->orderBy('name_ar')
                                    ->pluck('name_ar', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\TextInput::make('order_number')
                            ->label('رقم الطلبية')
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\DateTimePicker::make('order_date')
                            ->label('تاريخ الطلبية')
                            ->required()
                            ->native(false)
                            ->seconds(false),
                    ]),
                Forms\Components\Actions::make([
                    FormInlineAction::make('calculate')
                        ->label('احتساب')
                        ->action(fn () => $this->calculateTotals()),
                ]),
                Forms\Components\Repeater::make('lines')
                    ->label('')
                    ->schema([
                        Forms\Components\Select::make('service_product_id')
                            ->label('الصنف')
                            ->options(
                                ServiceProduct::query()
                                    ->where('company_id', $tenant->id)
                                    ->orderBy('name_ar')
                                    ->pluck('name_ar', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                if (! $state) {
                                    return;
                                }
                                $p = ServiceProduct::query()->find((int) $state);
                                if ($p === null) {
                                    return;
                                }
                                $set('unit_price', $p->sale_price);
                                $set('description', $p->name_ar);
                                $qty = (float) ($get('quantity') ?: 1);
                                $set('line_total', round($qty * (float) $p->sale_price, 2));
                            }),
                        Forms\Components\TextInput::make('description')
                            ->label('الوصف')
                            ->maxLength(500),
                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $q = (float) ($get('quantity') ?: 0);
                                $p = (float) ($get('unit_price') ?: 0);
                                $set('line_total', round($q * $p, 2));
                            }),
                        Forms\Components\TextInput::make('unit_price')
                            ->label('سعر الوحدة')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $q = (float) ($get('quantity') ?: 0);
                                $p = (float) ($get('unit_price') ?: 0);
                                $set('line_total', round($q * $p, 2));
                            }),
                        Forms\Components\TextInput::make('line_total')
                            ->label('الإجمالي')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                    ])
                    ->columns(5)
                    ->addActionLabel('اضافه سطر +')
                    ->defaultItems(0)
                    ->collapsible(),
                Forms\Components\TextInput::make('total_value')
                    ->label('القيمة الإجمالية')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function calculateTotals(): void
    {
        $lines = $this->data['lines'] ?? [];
        $total = 0.0;
        foreach ($lines as $i => $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['unit_price'] ?? 0);
            $lineTotal = round($qty * $price, 2);
            $lines[$i]['line_total'] = $lineTotal;
            $total += $lineTotal;
        }
        $this->data['lines'] = $lines;
        $this->data['total_value'] = round($total, 2);

        Notification::make()->title('تم تحديث الاحتساب')->success()->send();
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->form->validate();
        $data = $this->form->getState();

        Customer::query()
            ->where('company_id', $tenant->id)
            ->whereKey($data['customer_id'])
            ->firstOrFail();

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $row): bool => filled($row['description'] ?? null) || filled($row['service_product_id'] ?? null))
            ->values();

        $total = round((float) ($data['total_value'] ?? 0), 2);
        if ($lines->isNotEmpty()) {
            $total = round($lines->sum(fn (array $l): float => (float) ($l['line_total'] ?? 0)), 2);
        }

        DB::transaction(function () use ($tenant, $data, $lines, $total): void {
            $payload = [
                'company_id' => $tenant->id,
                'customer_id' => $data['customer_id'],
                'order_date' => $data['order_date'],
                'total_value' => $total,
            ];

            if (! empty($data['id'])) {
                $order = InventoryOrder::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $order->update($payload);
                $order->lines()->delete();
            } else {
                $payload['order_number'] = (int) ($data['order_number'] ?? InventoryOrder::nextOrderNumber($tenant->id));
                $payload['user_id'] = Auth::id();
                $order = InventoryOrder::query()->create($payload);
            }

            foreach ($lines as $index => $line) {
                InventoryOrderLine::query()->create([
                    'inventory_order_id' => $order->id,
                    'service_product_id' => $line['service_product_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'quantity' => (float) ($line['quantity'] ?? 1),
                    'unit_price' => (float) ($line['unit_price'] ?? 0),
                    'line_total' => (float) ($line['line_total'] ?? 0),
                    'sort_order' => $index,
                ]);
            }
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(OrdersScreenPage::getUrl());
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
                ->url(fn (): string => OrdersScreenPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'الطلبيات';
    }
}
