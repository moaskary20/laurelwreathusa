<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\ServiceProduct;
use App\Models\WarehouseOutwardVoucher;
use App\Models\WarehouseOutwardVoucherLine;
use App\Models\WarehouseRequisition;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormInlineAction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class WarehouseOutwardVoucherFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'warehouse-outward-voucher-form';

    protected static string $view = 'filament.pages.inventory.warehouse-outward-voucher-form';

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
            $voucher = WarehouseOutwardVoucher::query()
                ->where('company_id', $tenant->id)
                ->with(['lines'])
                ->findOrFail((int) $editId);

            $this->form->fill($this->voucherToFormData($voucher));
        } else {
            $this->form->fill([
                'voucher_number' => WarehouseOutwardVoucher::nextVoucherNumber($tenant->id),
                'voucher_date' => now()->toDateTimeString(),
                'warehouse_requisition_id' => null,
                'lines' => [],
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function voucherToFormData(WarehouseOutwardVoucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'voucher_number' => $voucher->voucher_number,
            'voucher_date' => $voucher->voucher_date?->toDateTimeString(),
            'warehouse_requisition_id' => $voucher->warehouse_requisition_id,
            'lines' => $voucher->lines->map(fn (WarehouseOutwardVoucherLine $line, int $index): array => [
                'service_product_id' => $line->service_product_id,
                'description' => $line->description,
                'quantity_requested' => $line->quantity_requested,
                'quantity_disbursed' => $line->quantity_disbursed,
                'difference' => $line->difference,
                'cost_center_id' => $line->cost_center_id,
                'sort_order' => $line->sort_order ?: $index,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    protected function applyLineDifference(array $line): array
    {
        $req = (float) ($line['quantity_requested'] ?? 0);
        $dis = (float) ($line['quantity_disbursed'] ?? 0);
        $line['difference'] = round($req - $dis, 4);

        return $line;
    }

    public function loadFromRequisition(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $rid = $this->data['warehouse_requisition_id'] ?? null;
        if (! $rid) {
            Notification::make()->title('اختر طلب صرف بضاعة أولاً')->warning()->send();

            return;
        }

        $requisition = WarehouseRequisition::query()
            ->where('company_id', $tenant->id)
            ->with('lines')
            ->findOrFail((int) $rid);

        $lines = [];
        foreach ($requisition->lines as $i => $l) {
            $qty = (float) $l->quantity;
            $line = [
                'service_product_id' => $l->service_product_id,
                'description' => $l->description,
                'quantity_requested' => $qty,
                'quantity_disbursed' => 0,
                'difference' => $qty,
                'cost_center_id' => $l->cost_center_id,
                'sort_order' => $i,
            ];
            $lines[] = $this->applyLineDifference($line);
        }

        $this->data['lines'] = $lines;

        Notification::make()->title('تم تحميل بنود الطلب')->success()->send();
    }

    public function calculateLineDifferences(): void
    {
        $lines = $this->data['lines'] ?? [];
        foreach ($lines as $i => $line) {
            $lines[$i] = $this->applyLineDifference(is_array($line) ? $line : []);
        }
        $this->data['lines'] = $lines;

        Notification::make()->title('تم تحديث الفوارق')->success()->send();
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return $form
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\DateTimePicker::make('voucher_date')
                    ->label('تاريخ سند صرف بضاعة')
                    ->required()
                    ->native(false)
                    ->seconds(false),
                Forms\Components\TextInput::make('voucher_number')
                    ->label('رقم سند صرف بضاعة')
                    ->disabled()
                    ->dehydrated(true),
                Forms\Components\Select::make('warehouse_requisition_id')
                    ->label('طلب صرف بضاعة')
                    ->placeholder('اختيار طلب صرف بضاعة')
                    ->required()
                    ->options(
                        WarehouseRequisition::query()
                            ->where('company_id', $tenant->id)
                            ->orderByDesc('request_date')
                            ->get()
                            ->mapWithKeys(fn (WarehouseRequisition $r): array => [
                                $r->id => $r->request_number.' — '.$r->request_date?->format('Y-m-d'),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Actions::make([
                    FormInlineAction::make('search')
                        ->label('بحث')
                        ->icon('heroicon-o-magnifying-glass')
                        ->action(fn () => $this->loadFromRequisition()),
                    FormInlineAction::make('calcQty')
                        ->label('احتساب الكمية المصروفة')
                        ->icon('heroicon-o-calculator')
                        ->action(fn () => $this->calculateLineDifferences()),
                ])
                    ->alignEnd(),
                Forms\Components\Repeater::make('lines')
                    ->label('بنود السند')
                    ->schema([
                        Forms\Components\Select::make('service_product_id')
                            ->label('الصنف')
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
                        Forms\Components\TextInput::make('description')
                            ->label('الوصف')
                            ->maxLength(500),
                        Forms\Components\TextInput::make('quantity_requested')
                            ->label('الكمية')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->calculateLineDifferencesQuiet()),
                        Forms\Components\TextInput::make('quantity_disbursed')
                            ->label('الكمية المصروفة')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->calculateLineDifferencesQuiet()),
                        Forms\Components\TextInput::make('difference')
                            ->label('الفرق')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\Select::make('cost_center_id')
                            ->label('مراكز التكلفة')
                            ->options(
                                CostCenter::query()
                                    ->where('company_id', $tenant->id)
                                    ->orderBy('name_ar')
                                    ->pluck('name_ar', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns(3)
                    ->addActionLabel('اضافه سطر +')
                    ->defaultItems(0)
                    ->reorderable(false),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function calculateLineDifferencesQuiet(): void
    {
        $lines = $this->data['lines'] ?? [];
        foreach ($lines as $i => $line) {
            $lines[$i] = $this->applyLineDifference(is_array($line) ? $line : []);
        }
        $this->data['lines'] = $lines;
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->calculateLineDifferencesQuiet();
        $this->form->validate();
        $data = $this->form->getState();

        WarehouseRequisition::query()
            ->where('company_id', $tenant->id)
            ->whereKey($data['warehouse_requisition_id'])
            ->firstOrFail();

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $row): bool => filled($row['service_product_id'] ?? null) || filled($row['description'] ?? null))
            ->values();

        DB::transaction(function () use ($tenant, $data, $lines): void {
            $payload = [
                'company_id' => $tenant->id,
                'voucher_date' => $data['voucher_date'],
                'warehouse_requisition_id' => $data['warehouse_requisition_id'],
            ];

            if (! empty($data['id'])) {
                $voucher = WarehouseOutwardVoucher::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $voucher->update($payload);
                $voucher->lines()->delete();
                $order = $voucher;
            } else {
                $payload['voucher_number'] = (int) ($data['voucher_number'] ?? WarehouseOutwardVoucher::nextVoucherNumber($tenant->id));
                $payload['user_id'] = Auth::id();
                $order = WarehouseOutwardVoucher::query()->create($payload);
            }

            foreach ($lines as $index => $line) {
                $line = $this->applyLineDifference($line);
                WarehouseOutwardVoucherLine::query()->create([
                    'warehouse_outward_voucher_id' => $order->id,
                    'service_product_id' => $line['service_product_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'quantity_requested' => (float) ($line['quantity_requested'] ?? 0),
                    'quantity_disbursed' => (float) ($line['quantity_disbursed'] ?? 0),
                    'difference' => (float) ($line['difference'] ?? 0),
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(WarehouseOutwardVoucherPage::getUrl());
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
                ->url(fn (): string => WarehouseOutwardVoucherPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'سند صرف مستودع';
    }
}
