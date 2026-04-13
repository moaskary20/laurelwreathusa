<?php

namespace App\Support;

/**
 * تسميات مسطّحة لاستخدامها في نماذج المستخدم (CheckboxList).
 * المفاتيح الحالية تتبع {@see UserPermissionRegistry} (صفحات وموارد النظام).
 *
 * @return array<string, string>
 */
final class UserPermissionLabels
{
    public static function all(): array
    {
        return UserPermissionRegistry::flat();
    }
}
