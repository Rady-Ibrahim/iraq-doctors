<?php

namespace App\Support;

use InvalidArgumentException;

class PhoneNormalizer
{
    public const IRAQ_COUNTRY_CODE = '964';

    public const EGYPT_COUNTRY_CODE = '20';

    /**
     * Normalize phone to E.164 (+CC...).
     *
     * @throws InvalidArgumentException
     */
    public static function toE164(string $phone, ?string $defaultCountryCode = null): string
    {
        $phone = trim($phone);

        if ($phone === '') {
            throw new InvalidArgumentException('رقم الهاتف مطلوب');
        }

        $defaultCountryCode = $defaultCountryCode
            ?? (string) config('phone.default_country_code', self::IRAQ_COUNTRY_CODE);

        if (str_starts_with($phone, '+')) {
            $digits = preg_replace('/\D/', '', substr($phone, 1));

            return self::formatE164FromDigits($digits);
        }

        $digits = preg_replace('/\D/', '', $phone);

        return self::formatE164FromDigits($digits, $defaultCountryCode);
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function formatE164FromDigits(string $digits, ?string $defaultCountryCode = null): string
    {
        if ($digits === '') {
            throw new InvalidArgumentException('رقم الهاتف غير صحيح');
        }

        $defaultCountryCode ??= (string) config('phone.default_country_code', self::IRAQ_COUNTRY_CODE);

        foreach (self::supportedCountryCodes() as $countryCode) {
            if (str_starts_with($digits, $countryCode)) {
                $national = substr($digits, strlen($countryCode));

                if (self::isValidNationalNumber($national, $countryCode)) {
                    return '+' . $countryCode . $national;
                }

                throw new InvalidArgumentException(self::invalidMessage($countryCode));
            }
        }

        if (str_starts_with($digits, '0')) {
            $national = ltrim($digits, '0');
            $countryCode = self::detectCountryFromNational($national, $defaultCountryCode);

            if (!self::isValidNationalNumber($national, $countryCode)) {
                throw new InvalidArgumentException(self::invalidMessage($countryCode));
            }

            return '+' . $countryCode . $national;
        }

        $countryCode = self::detectCountryFromNational($digits, $defaultCountryCode);

        if (self::isValidNationalNumber($digits, $countryCode)) {
            return '+' . $countryCode . $digits;
        }

        throw new InvalidArgumentException('رقم الهاتف غير صحيح');
    }

    /**
     * @return array<int, string>
     */
    private static function supportedCountryCodes(): array
    {
        $codes = config('phone.supported_country_codes', [self::IRAQ_COUNTRY_CODE, self::EGYPT_COUNTRY_CODE]);

        return array_values(array_unique($codes));
    }

    private static function detectCountryFromNational(string $national, string $defaultCountryCode): string
    {
        if (self::isValidNationalNumber($national, self::IRAQ_COUNTRY_CODE)) {
            return self::IRAQ_COUNTRY_CODE;
        }

        if (self::isValidNationalNumber($national, self::EGYPT_COUNTRY_CODE)) {
            return self::EGYPT_COUNTRY_CODE;
        }

        return $defaultCountryCode;
    }

    private static function isValidNationalNumber(string $national, string $countryCode): bool
    {
        return match ($countryCode) {
            self::IRAQ_COUNTRY_CODE => (bool) preg_match('/^7[0-9]{9}$/', $national),
            self::EGYPT_COUNTRY_CODE => (bool) preg_match('/^1[0-9]{9}$/', $national),
            default => false,
        };
    }

    private static function invalidMessage(string $countryCode): string
    {
        return match ($countryCode) {
            self::EGYPT_COUNTRY_CODE => 'رقم الهاتف المصري غير صحيح',
            default => 'رقم الهاتف العراقي غير صحيح',
        };
    }

    /**
     * Legacy local format for backward-compatible DB lookup.
     */
    public static function toLocalFormat(string $e164): string
    {
        $e164 = self::toE164($e164);
        $digits = substr($e164, 1);

        if (str_starts_with($digits, self::IRAQ_COUNTRY_CODE)) {
            return '0' . substr($digits, strlen(self::IRAQ_COUNTRY_CODE));
        }

        if (str_starts_with($digits, self::EGYPT_COUNTRY_CODE)) {
            return '0' . substr($digits, strlen(self::EGYPT_COUNTRY_CODE));
        }

        return $e164;
    }

    /**
     * Mask phone for display: +9647*****123
     */
    public static function mask(string $phone): string
    {
        try {
            $e164 = self::toE164($phone);
        } catch (InvalidArgumentException) {
            return $phone;
        }

        $len = strlen($e164);

        if ($len <= 8) {
            return $e164;
        }

        return substr($e164, 0, 5) . str_repeat('*', max(3, $len - 8)) . substr($e164, -3);
    }

    /**
     * All common stored variants to match legacy rows before migration to E.164.
     *
     * @return array<int, string>
     */
    public static function lookupVariants(string $phone): array
    {
        try {
            $e164 = self::toE164($phone);
        } catch (InvalidArgumentException) {
            return array_values(array_unique([trim($phone)]));
        }

        $local = self::toLocalFormat($e164);
        $digitsOnly = preg_replace('/\D/', '', $e164);

        $variants = [
            $e164,
            $local,
            $digitsOnly,
        ];

        foreach (self::supportedCountryCodes() as $cc) {
            if (str_starts_with($digitsOnly, $cc)) {
                $variants[] = ltrim(substr($digitsOnly, strlen($cc)), '0');
                $variants[] = substr($digitsOnly, strlen($cc));
            }
        }

        return array_values(array_unique(array_filter($variants)));
    }
}
