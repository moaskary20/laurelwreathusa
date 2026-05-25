<?php

namespace App\Support;

use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\File;

final class AdminTheme
{
    public const DEFAULT_PRIMARY = '#323991';

    public const DEFAULT_SECONDARY = '#5B8FD9';

    private const STORAGE_PATH = 'app/admin-theme.json';

    /**
     * @return array{primary: string, secondary: string}
     */
    public static function all(): array
    {
        $stored = self::readStored();

        return [
            'primary' => self::normalizeHex($stored['primary'] ?? self::DEFAULT_PRIMARY),
            'secondary' => self::normalizeHex($stored['secondary'] ?? self::DEFAULT_SECONDARY),
        ];
    }

    public static function primary(): string
    {
        return self::all()['primary'];
    }

    public static function secondary(): string
    {
        return self::all()['secondary'];
    }

    /**
     * @param  array{primary?: string, secondary?: string}  $colors
     */
    public static function save(array $colors): void
    {
        $payload = [
            'primary' => self::normalizeHex($colors['primary'] ?? self::DEFAULT_PRIMARY),
            'secondary' => self::normalizeHex($colors['secondary'] ?? self::DEFAULT_SECONDARY),
        ];

        File::ensureDirectoryExists(storage_path('app'));

        File::put(
            storage_path(self::STORAGE_PATH),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    public static function resetToDefaults(): void
    {
        if (File::exists(storage_path(self::STORAGE_PATH))) {
            File::delete(storage_path(self::STORAGE_PATH));
        }
    }

    /**
     * @return array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}
     */
    public static function primaryFilamentPalette(): array
    {
        return Color::hex(self::primary());
    }

    /**
     * @return array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}
     */
    public static function secondaryFilamentPalette(): array
    {
        return Color::hex(self::secondary());
    }

    public static function primaryLight(): string
    {
        return self::adjustBrightness(self::primary(), 28);
    }

    public static function primaryDark(): string
    {
        return self::adjustBrightness(self::primary(), -22);
    }

    public static function secondaryLight(): string
    {
        return self::adjustBrightness(self::secondary(), 18);
    }

    public static function surfaceBackground(): string
    {
        return self::adjustBrightness(self::primary(), -38);
    }

    public static function surfaceCard(): string
    {
        return self::adjustBrightness(self::primary(), -24);
    }

    public static function surfaceElevated(): string
    {
        return self::adjustBrightness(self::primary(), -14);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim(self::normalizeHex($hex), '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function rgbString(string $hex): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        return "{$r}, {$g}, {$b}";
    }

    public static function normalizeHex(string $hex): string
    {
        $hex = trim($hex);

        if ($hex === '') {
            return self::DEFAULT_PRIMARY;
        }

        if (! str_starts_with($hex, '#')) {
            $hex = '#'.$hex;
        }

        if (preg_match('/^#([0-9a-fA-F]{3})$/', $hex, $matches)) {
            $chars = str_split($matches[1]);

            return '#'.strtoupper($chars[0].$chars[0].$chars[1].$chars[1].$chars[2].$chars[2]);
        }

        if (preg_match('/^#([0-9a-fA-F]{6})$/', $hex, $matches)) {
            return '#'.strtoupper($matches[1]);
        }

        return self::DEFAULT_PRIMARY;
    }

    public static function isValidHex(string $hex): bool
    {
        $normalized = trim($hex);

        if ($normalized !== '' && ! str_starts_with($normalized, '#')) {
            $normalized = '#'.$normalized;
        }

        return (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $normalized);
    }

    private static function adjustBrightness(string $hex, int $percent): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        $adjust = static fn (int $channel): int => (int) max(0, min(255, $channel + ($channel * $percent / 100)));

        return sprintf(
            '#%02X%02X%02X',
            $adjust($r),
            $adjust($g),
            $adjust($b),
        );
    }

    /**
     * @return array{primary?: string, secondary?: string}
     */
    private static function readStored(): array
    {
        $path = storage_path(self::STORAGE_PATH);

        if (! File::exists($path)) {
            return [];
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
