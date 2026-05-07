<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\FinishedGoodsInwardVoucher;
use App\Models\FinishedGoodsInwardVoucherLine;
use App\Models\ServiceProduct;
use App\Services\Accounting\ChartOfAccountsService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormInlineAction;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class FinishedGoodsInwardVoucherFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'finished-goods-inward-voucher-form';

    protected static string $view = 'filament.pages.inventory.finished-goods-inward-voucher-form';

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
            $voucher = FinishedGoodsInwardVoucher::query()
                ->where('company_id', $tenant->id)
                ->with(['lines'])
                ->findOrFail((int) $editId);

            $this->form->fill($this->voucherToFormData($voucher));
        } else {
            $this->form->fill([
                'voucher_number' => FinishedGoodsInwardVoucher::nextVoucherNumber($tenant->id),
                'voucher_date' => now()->toDateTimeString(),
                'credit_account_group_id' => null,
                'total_cost' => 0,
                'lines' => [],
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function voucherToFormData(FinishedGoodsInwardVoucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'voucher_number' => $voucher->voucher_number,
            'voucher_date' => $voucher->voucher_date?->toDateTimeString(),
            'credit_account_group_id' => $voucher->credit_account_group_id,
            'total_cost' => $voucher->total_cost,
            'lines' => $voucher->lines->map(fn (FinishedGoodsInwardVoucherLine $line, int $index): array => [
                'service_product_id' => $line->service_product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_cost' => $line->unit_cost,
                'line_total' => $line->line_total,
                'sort_order' => $line->sort_order ?: $index,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    protected function applyLineCost(array $line): array
    {
        $q = (float) ($line['quantity'] ?? 0);
        $u = (float) ($line['unit_cost'] ?? 0);
        $line['line_total'] = round($q * $u, 2);

        return $line;
    }

    public function calculateCost(bool $notify = true): void
    {
        $lines = $this->data['lines'] ?? [];
        $sum = 0.0;
        foreach ($lines as $i => $line) {
            $lines[$i] = $this->applyLineCost(is_array($line) ? $line : []);
            $sum += (float) $lines[$i]['line_total'];
        }
        $this->data['lines'] = $lines;
        $this->data['total_cost'] = round($sum, 2);

        if ($notify) {
            Notification::make()->title('تم احتساب التكلفة')->success()->send();
        }
    }

    public function calculateCostQuiet(): void
    {
        $this->calculateCost(false);
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return $form
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\DateTimePicker::make('voucher_date')
                    ->label('تاريخ الفاتورة')
                    ->required()
                    ->native(false)
                    ->seconds(false),
                Forms\Components\TextInput::make('voucher_number')
                    ->label('رقم السند')
                    ->disabled()
                    ->dehydrated(true),
                Forms\Components\Select::make('credit_account_group_id')
                    ->label('الحساب الدائن')
                    ->placeholder('المجموعات')
                    ->options(AccountGroup::indentedPostingOptionsForCompany($tenant->id))
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Actions::make([
                    FormInlineAction::make('calcCost')
                        ->label('احتساب التكلفة')
                        ->icon('heroicon-o-calculator')
                        ->action(fn () => $this->calculateCost(true)),
                ])
                    ->alignEnd(),
                Forms\Components\Repeater::make('lines')
                    ->label('بنود الإنتاج التام')
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
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) use ($tenant): void {
                                if (! $state) {
                                    return;
                                }
                                $p = ServiceProduct::query()
                                    ->where('company_id', $tenant->id)
                                    ->find((int) $state);
                                if ($p === null) {
                                    return;
                                }
                                $set('description', $p->name_ar);
                                $set('unit_cost', $p->unit_cost ?? $p->sale_price ?? 0);
                                $this->calculateCostQuiet();
                            }),
                        Forms\Components\TextInput::make('description')
                            ->label('الوصف')
                            ->maxLength(500),
                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->calculateCostQuiet()),
                        Forms\Components\TextInput::make('unit_cost')
                            ->label('تكلفة الوحدة')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->calculateCostQuiet()),
                        Forms\Components\TextInput::make('line_total')
                            ->label('الإجمالي')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                    ])
                    ->columns(2)
                    ->addActionLabel('اضافة سطر +')
                    ->defaultItems(0),
                Forms\Components\TextInput::make('total_cost')
                    ->label('إجمالي التكلفة')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->calculateCost(false);
        $this->form->validate();
        $data = $this->form->getState();

        if (! empty($data['credit_account_group_id'])) {
            app(ChartOfAccountsService::class)->assertCanPostToAccount($tenant->id, (int) $data['credit_account_group_id']);
        }

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $row): bool => filled($row['service_product_id'] ?? null) || filled($row['description'] ?? null))
            ->values();

        $total = 0.0;
        foreach ($lines as $line) {
            $line = $this->applyLineCost($line);
            $total += (float) $line['line_total'];
        }
        $total = round($total, 2);

        DB::transaction(function () use ($tenant, $data, $lines, $total): void {
            $payload = [
                'company_id' => $tenant->id,
                'voucher_date' => $data['voucher_date'],
                'credit_account_group_id' => $data['credit_account_group_id'] ?? null,
                'total_cost' => $total,
            ];

            if (! empty($data['id'])) {
                $voucher = FinishedGoodsInwardVoucher::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $voucher->update($payload);
                $voucher->lines()->delete();
                $order = $voucher;
            } else {
                $payload['voucher_number'] = (int) ($data['voucher_number'] ?? FinishedGoodsInwardVoucher::nextVoucherNumber($tenant->id));
                $payload['user_id'] = Auth::id();
                $order = FinishedGoodsInwardVoucher::query()->create($payload);
            }

            foreach ($lines as $index => $line) {
                $line = $this->applyLineCost($line);
                FinishedGoodsInwardVoucherLine::query()->create([
                    'finished_goods_inward_voucher_id' => $order->id,
                    'service_product_id' => $line['service_product_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'quantity' => (float) ($line['quantity'] ?? 1),
                    'unit_cost' => (float) ($line['unit_cost'] ?? 0),
                    'line_total' => (float) $line['line_total'],
                    'sort_order' => $index,
                ]);
            }
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(FinishedGoodsInwardVoucherPage::getUrl());
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
                ->url(fn (): string => FinishedGoodsInwardVoucherPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'سند ادخال انتاج تام';
    }
}
