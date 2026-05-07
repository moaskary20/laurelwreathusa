<?php

namespace App\Support;

/**
 * صلاحيات الوصول لكل صفحة ومورد في اللوحة، مجمّعة حسب مجموعات القائمة.
 *
 * المفتاح = معرّف مستقر يُخزَّن في users.permissions (مصفوفة مفاتيح مفعّلة).
 *
 * @return array<string, array{label: string, items: array<string, string>}>
 */
final class UserPermissionRegistry
{
    /**
     * @return array<string, array{label: string, items: array<string, string>}>
     */
    public static function grouped(): array
    {
        return [
            'administration' => [
                'label' => 'إدارة',
                'items' => [
                    'dashboard' => 'الصفحة الرئيسية',
                    'offices' => 'المكاتب',
                    'company-information' => 'معلومات الشركة',
                    'users' => 'المستخدمين',
                    'bank-accounts' => 'بيانات البنك',
                    'invoice-management' => 'إدارة الفواتير',
                    'currency-selection' => 'اختيار العملة',
                    'tax-definition' => 'تعريف الضريبة',
                    'trade-discount' => 'خصم تجاري',
                    'download' => 'تحميل',
                    'user-permissions-page' => 'صلاحيات المستخدمين',
                    'companies' => 'قائمة الشركات',
                ],
            ],
            'customers' => [
                'label' => 'العملاء',
                'items' => [
                    'customers-list-page' => 'قائمة العملاء',
                    'customer-account-statement-page' => 'كشف حساب عملاء',
                    'customer-invoices-page' => 'الفواتير',
                    'services-and-products-list-page' => 'قائمة الخدمات و المنتجات',
                    'sales-order-page' => 'امر البيع',
                    'cost-centers-page' => 'مراكز التكلفه',
                    'analytical-page' => 'تحليلي',
                ],
            ],
            'suppliers' => [
                'label' => 'الموردين',
                'items' => [
                    'suppliers-list-page' => 'قائمة الموردين',
                    'suppliers-account-statement-page' => 'كشف حساب الموردين',
                    'purchase-invoices-page' => 'فواتير المشتريات',
                    'purchase-order-page' => 'امر الشراء',
                ],
            ],
            'inventory' => [
                'label' => 'المخزون',
                'items' => [
                    'warehouse-definition-page' => 'تعريف المستودعات',
                    'unit-of-measurement-definition-page' => 'تعريف وحدة القياس',
                    'inventory-items-list-page' => 'قائمة الاصناف',
                    'orders-screen-page' => 'شاشة الطلبيات',
                    'goods-inward-voucher-page' => 'سند ادخال المواد',
                    'goods-outward-voucher-page' => 'سند اخراج بضاعه',
                    'materials-entry-screen-page' => 'شاشة ادخال المواد',
                    'warehouse-outward-voucher-page' => 'سند اخراج مستودع',
                    'finished-goods-inward-voucher-page' => 'سند ادخال انتاج تام',
                    'warehouse-requisition-page' => 'طلب صرف مستودع',
                ],
            ],
            'accounting' => [
                'label' => 'المحاسبة',
                'items' => [
                    'chart-of-accounts-page' => 'شجرة الحسابات',
                    'journal-entries-page' => 'القيود',
                    'receipt-vouchers-page' => 'سندات قبض',
                    'payment-vouchers-page' => 'سندات صرف',
                    'debit-note-page' => 'اشعار مدين',
                    'credit-note-page' => 'اشعار دائن',
                    'bank-deposit-page' => 'ايداع بنكي',
                    'accounting-account-statement-page' => 'كشف حساب',
                    'trial-balance-page' => 'ميزان المراجعة',
                    'aging-balances-page' => 'اعمار الذمم',
                    'close-or-open-month-page' => 'اغلاق او فتح شهر',
                    'fiscal-year-closing-page' => 'إغلاق السنة المالية',
                ],
            ],
            'assets' => [
                'label' => 'الموجودات',
                'items' => [
                    'fixed-assets-register-page' => 'سجل الموجودات الثابته',
                    'add-asset-page' => 'اضافة',
                    'asset-disposal-page' => 'استبعاد',
                    'disposal-reports-page' => 'تقارير الاستبعاد',
                ],
            ],
            'payroll' => [
                'label' => 'كشف الرواتب',
                'items' => [
                    'employee-definition-page' => 'تعريف الموظف',
                    'allowances-definition-page' => 'تعريف العلاوات',
                    'deductions-definition-page' => 'تعريف الاقتطاعات',
                    'payroll-statement-page' => 'كشف الرواتب (1)',
                ],
            ],
            'reports' => [
                'label' => 'تقارير',
                'items' => [
                    'income-statement-page' => 'قائمة الدخل',
                    'balance-sheet-page' => 'قائمة المركز المالي',
                    'revenue-by-customer-page' => 'الإيرادات حسب العميل',
                    'revenue-by-service-product-page' => 'الإيرادات حسب نوع الخدمة والمنتج',
                    'vat-customers-report-page' => 'ضريبة القيمة المضافة للعملاء',
                    'vat-suppliers-report-page' => 'ضريبة القيمة المضافة للموردين',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function flat(): array
    {
        $out = [];
        foreach (self::grouped() as $block) {
            foreach ($block['items'] as $key => $label) {
                $out[$key] = $label;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function allKeys(): array
    {
        return array_keys(self::flat());
    }
}
