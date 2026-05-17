<?php

namespace App\Support;

/**
 * عناوين مجموعات القائمة الجانبية (متزامنة مع AdminPanelProvider).
 *
 * @return list<string>
 */
final class AdminNavigationGroupLabels
{
    public static function all(): array
    {
        return [
            'إدارة',
            'العملاء',
            'الموردين',
            'المخزون',
            'المحاسبة',
            'الموجودات',
            'كشف الرواتب',
            'تقارير',
        ];
    }
}
