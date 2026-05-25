<?php

namespace App\Support;

final class AppBrand
{
    public const LOGO_PATH = 'images/logo.png';

    public static function logoUrl(): string
    {
        return asset(self::LOGO_PATH);
    }
}
