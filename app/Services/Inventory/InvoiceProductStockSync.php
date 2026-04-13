<?php

namespace App\Services\Inventory;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\ServiceProduct;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class InvoiceProductStockSync
{
    /**
     * @param  Collection<int, \App\Models\InvoiceLine>  $previousLines
     */
    public function syncCustomerInvoice(Invoice $invoice, Collection $previousLines): void
    {
        $oldQty = $this->aggregateProductQuantities($previousLines);

        $invoice->loadMissing(['lines.serviceProduct']);
        $newQty = $this->aggregateProductQuantities($invoice->lines);

        $ids = array_unique(array_merge(array_keys($oldQty), array_keys($newQty)));
        sort($ids);

        foreach ($ids as $productId) {
            $o = (float) ($oldQty[$productId] ?? 0);
            $n = (float) ($newQty[$productId] ?? 0);
            $delta = $o - $n;

            if (abs($delta) < 0.0000001) {
                continue;
            }

            $product = ServiceProduct::query()
                ->where('company_id', $invoice->company_id)
                ->whereKey($productId)
                ->lockForUpdate()
                ->firstOrFail();

            $newStock = (float) $product->stock_quantity + $delta;
            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'lines' => 'الكمية المتاحة في المخزون غير كافية لهذا الصنف.',
                ]);
            }

            $product->update(['stock_quantity' => $newStock]);
        }
    }

    /**
     * @param  Collection<int, \App\Models\PurchaseInvoiceLine>  $previousLines
     */
    public function syncPurchaseInvoice(PurchaseInvoice $invoice, Collection $previousLines, Company $company): void
    {
        $oldQty = $this->aggregateProductQuantities($previousLines);

        $invoice->loadMissing(['lines.serviceProduct']);
        $newQty = $this->aggregateProductQuantities($invoice->lines);

        $ids = array_unique(array_merge(array_keys($oldQty), array_keys($newQty)));
        sort($ids);

        foreach ($ids as $productId) {
            $o = (float) ($oldQty[$productId] ?? 0);
            $n = (float) ($newQty[$productId] ?? 0);
            $delta = $n - $o;

            if (abs($delta) < 0.0000001) {
                continue;
            }

            $product = ServiceProduct::query()
                ->where('company_id', $invoice->company_id)
                ->whereKey($productId)
                ->lockForUpdate()
                ->firstOrFail();

            $stockBefore = (float) $product->stock_quantity;
            $newStock = $stockBefore + $delta;

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'lines' => 'لا يمكن خصم كمية أكبر من رصيد المخزون لهذا الصنف.',
                ]);
            }

            $attrs = ['stock_quantity' => $newStock];

            if ($delta > 0 && ($company->inventory_pricing ?? '') === 'average') {
                $totals = $this->purchaseQtyAndCostForProduct($invoice->lines, $productId);
                $incomingQty = $delta;
                $incomingCost = $totals['qty'] > 0
                    ? (float) $totals['cost'] * ($incomingQty / (float) $totals['qty'])
                    : 0;
                $den = $stockBefore + $incomingQty;
                if ($den > 0) {
                    $attrs['unit_cost'] = round(
                        ($stockBefore * (float) $product->unit_cost + $incomingCost) / $den,
                        2
                    );
                }
            }

            $product->update($attrs);
        }
    }

    /**
     * @param  Collection<int, \App\Models\InvoiceLine|\App\Models\PurchaseInvoiceLine>  $lines
     * @return array<int, float>
     */
    private function aggregateProductQuantities(Collection $lines): array
    {
        $map = [];
        foreach ($lines as $line) {
            if (! $line->service_product_id) {
                continue;
            }
            $sp = $line->relationLoaded('serviceProduct')
                ? $line->serviceProduct
                : ServiceProduct::query()->find($line->service_product_id);
            if (! $sp || $sp->kind !== 'product') {
                continue;
            }
            $id = (int) $line->service_product_id;
            $map[$id] = ($map[$id] ?? 0) + (float) $line->quantity;
        }

        return $map;
    }

    /**
     * @param  Collection<int, \App\Models\PurchaseInvoiceLine>  $lines
     * @return array{qty: float, cost: float}
     */
    private function purchaseQtyAndCostForProduct(Collection $lines, int $productId): array
    {
        $qty = 0.0;
        $cost = 0.0;
        foreach ($lines as $line) {
            if ((int) $line->service_product_id !== $productId) {
                continue;
            }
            $sp = $line->relationLoaded('serviceProduct')
                ? $line->serviceProduct
                : ServiceProduct::query()->find($line->service_product_id);
            if (! $sp || $sp->kind !== 'product') {
                continue;
            }
            $qty += (float) $line->quantity;
            $cost += (float) $line->line_total;
        }

        return ['qty' => $qty, 'cost' => $cost];
    }
}
