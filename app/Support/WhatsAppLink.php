<?php

namespace App\Support;

final class WhatsAppLink
{
    /**
     * Normalisasi ke format angka internasional Indonesia untuk wa.me (tanpa +), contoh: 6281234567890.
     */
    public static function normalizeToWaDigits(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            return strlen($digits) >= 11 ? $digits : null;
        }

        if (str_starts_with($digits, '0')) {
            $rest = substr($digits, 1);

            return strlen($rest) >= 9 ? '62'.$rest : null;
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return strlen($digits) >= 9 ? '62'.$digits : null;
    }

    public static function welcomeMessage(): string
    {
        return (string) config('whatsapp.welcome_message');
    }

    public static function buildWelcomeUrl(?string $phone): ?string
    {
        $wa = self::normalizeToWaDigits($phone);
        if ($wa === null) {
            return null;
        }

        $text = rawurlencode(self::welcomeMessage());

        return "https://wa.me/{$wa}?text={$text}";
    }
}
