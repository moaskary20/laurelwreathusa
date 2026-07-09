<?php

namespace App\Support\Accounting;

/**
 * الشجرة المحاسبية الافتراضية للشركات الجديدة.
 *
 * @phpstan-type AccountDefinition array{
 *     code: string,
 *     name_ar: string,
 *     account_type: string,
 *     normal_balance: string,
 *     parent_code: string|null
 * }
 */
final class DefaultChartOfAccounts
{
    /**
     * @return list<AccountDefinition>
     */
    public static function definitions(): array
    {
        return [
            ['code' => '1000000', 'name_ar' => 'الموجودات', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => null],
            ['code' => '1100000', 'name_ar' => 'الموجودات المتداولة', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000000'],
            ['code' => '1110000', 'name_ar' => 'النقد وما في حكمه', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100000'],
            ['code' => '1111000', 'name_ar' => 'نقد في الصندوق', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1110000'],
            ['code' => '1112000', 'name_ar' => 'نقد لدى البنوك', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1110000'],
            ['code' => '1120000', 'name_ar' => 'الذمم المدينة', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100000'],
            ['code' => '1130000', 'name_ar' => 'المخزون', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100000'],
            ['code' => '1131000', 'name_ar' => 'مواد اولية خام', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1130000'],
            ['code' => '1132000', 'name_ar' => 'تحت التصنيع', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1130000'],
            ['code' => '1133000', 'name_ar' => 'بضاعة جاهزة', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1130000'],
            ['code' => '1140000', 'name_ar' => 'مصاريف مدفوعة مقدما', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100000'],
            ['code' => '1150000', 'name_ar' => 'امانات ضريبة الدخل 5%', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100000'],
            ['code' => '1160000', 'name_ar' => 'امانات ضريبة المبيعات - مدين', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100000'],
            ['code' => '1170000', 'name_ar' => 'تأمينات مستردة', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1100000'],
            ['code' => '1200000', 'name_ar' => 'الموجودات غير المتداولة', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1000000'],
            ['code' => '1210000', 'name_ar' => 'الممتلكات و المعدات', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1200000'],
            ['code' => '12100001', 'name_ar' => 'سيارات', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1210000'],
            ['code' => '12100002', 'name_ar' => 'اثاث ومفروشات', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1210000'],
            ['code' => '1220000', 'name_ar' => 'موجودات غير ملموسة', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent_code' => '1200000'],

            ['code' => '2000000', 'name_ar' => 'المطلوبات', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => null],
            ['code' => '2100000', 'name_ar' => 'المطلوبات المتداولة', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000000'],
            ['code' => '2110000', 'name_ar' => 'الذمم الدائنة', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100000'],
            ['code' => '2120000', 'name_ar' => 'البنوك الدائنة', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100000'],
            ['code' => '2130000', 'name_ar' => 'المصاريف المستحقة', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100000'],
            ['code' => '2131000', 'name_ar' => 'رواتب مستحقة', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2130000'],
            ['code' => '2140000', 'name_ar' => 'قروض قصيرة الاجل', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100000'],
            ['code' => '2150000', 'name_ar' => 'مخصص ضريبة الدخل', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100000'],
            ['code' => '2160000', 'name_ar' => 'امانات الضمان الاجتماعي', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100000'],
            ['code' => '2170000', 'name_ar' => 'امانات ضريبة المبيعات - دائن', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2100000'],
            ['code' => '2200000', 'name_ar' => 'المطلوبات غير المتداولة', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2000000'],
            ['code' => '2210000', 'name_ar' => 'قروض طويلة الاجل', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2200000'],
            ['code' => '2220000', 'name_ar' => 'مخصص تعويض نهاية الخدمة', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent_code' => '2200000'],

            ['code' => '3000000', 'name_ar' => 'حقوق الملكية', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => null],
            ['code' => '3100000', 'name_ar' => 'حقوق الملكية', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3000000'],
            ['code' => '3110000', 'name_ar' => 'رأس المال', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3100000'],
            ['code' => '3120000', 'name_ar' => 'الارباح (الخسائر) المتراكمة', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3100000'],
            ['code' => '3130000', 'name_ar' => 'جاري صاحب المؤسسة', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3100000'],
            ['code' => '3180000', 'name_ar' => 'الارصدة الافتتاحية', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent_code' => '3100000'],

            ['code' => '4000000', 'name_ar' => 'الايرادات', 'account_type' => 'revenue', 'normal_balance' => 'credit', 'parent_code' => null],
            ['code' => '5000000', 'name_ar' => 'تكلفة الايرادات', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null],
            ['code' => '6000000', 'name_ar' => 'المصاريف الادارية و العمومية', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null],
            ['code' => '6100000', 'name_ar' => 'رواتب واجور', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6000000'],
            ['code' => '6200000', 'name_ar' => 'مساهمة الشركة في الضمان الاجتماعي', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6000000'],
            ['code' => '6300000', 'name_ar' => 'علاوات', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => '6000000'],
            ['code' => '7000000', 'name_ar' => 'مصاريف البيع والتوزيع', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null],
            ['code' => '8000000', 'name_ar' => 'المصاريف والايرادات الاخرى', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null],
            ['code' => '9000000', 'name_ar' => 'مصروف ضريبة الدخل', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent_code' => null],
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_column(self::definitions(), 'code');
    }

    /**
     * ربط أكواد الشجرة القديمة (4 أرقام) بالأكواد الجديدة (7 أرقام).
     *
     * @return array<string, string>
     */
    public static function legacyCodeMap(): array
    {
        return [
            '1000' => '1000000',
            '1100' => '1110000',
            '1110' => '1111000',
            '1120' => '1112000',
            '1200' => '1120000',
            '1300' => '1130000',
            '1400' => '1210000',
            '2000' => '2000000',
            '2100' => '2110000',
            '2200' => '2170000',
            '2300' => '2160000',
            '3000' => '3000000',
            '3100' => '3110000',
            '4000' => '4000000',
            '4100' => '4000000',
            '4200' => '4000000',
            '5000' => '5000000',
            '5100' => '5000000',
            '5200' => '6100000',
            '5300' => '8000000',
        ];
    }
}
