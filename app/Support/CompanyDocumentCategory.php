<?php

namespace App\Support;

/**
 * بنود تصنيف مستندات صفحة التحميل.
 */
final class CompanyDocumentCategory
{
    public const PURCHASE_INVOICES = 'purchase_invoices';

    public const SALES = 'sales';

    public const PAYMENT_VOUCHERS = 'payment_vouchers';

    public const RECEIPT_VOUCHERS = 'receipt_vouchers';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::PURCHASE_INVOICES => 'فواتير شراء',
            self::SALES => 'البيع',
            self::PAYMENT_VOUCHERS => 'سندات صرف',
            self::RECEIPT_VOUCHERS => 'سندات قبض',
        ];
    }

    public static function label(string $key): string
    {
        return self::labels()[$key] ?? $key;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::labels());
    }
}
