<?php

namespace App\Support;

/**
 * Build a dynamic QRIS payload from a static merchant QRIS string.
 *
 * The merchant pastes the static payload from their QRIS app. Every payment
 * page then injects the invoice total and recalculates the CRC so the customer
 * does not have to type the amount.
 */
final class QrisPayload
{
    /**
     * Check that the payload is well-formed and its CRC is correct.
     */
    public static function isValid(string $payload): bool
    {
        $payload = trim($payload);

        if (! str_starts_with($payload, '000201')) {
            return false;
        }

        $tags = self::parseTags($payload);

        if ($tags === []) {
            return false;
        }

        $last = end($tags);

        if ($last[0] !== '63' || strlen($last[1]) !== 4) {
            return false;
        }

        $expected = self::crc16(substr($payload, 0, -4));

        if (! hash_equals(strtoupper($last[1]), $expected)) {
            return false;
        }

        if (self::value($tags, '00') !== '01') {
            return false;
        }

        $currency = self::value($tags, '53');

        return $currency === null || $currency === '360';
    }

    /**
     * Return the static payload with a fixed amount and a fresh CRC.
     */
    public static function forAmount(string $payload, string $amount): ?string
    {
        if (! self::isValid($payload)) {
            return null;
        }

        $amount = self::normalizeAmount($amount);

        if ($amount === null) {
            return null;
        }

        $tags = self::parseTags(trim($payload));

        // Drop the old CRC and any amount the merchant left in the payload.
        array_pop($tags);
        $tags = array_values(array_filter(
            $tags,
            fn (array $tag): bool => $tag[0] !== '54',
        ));

        $hasPointOfInitiation = false;

        foreach ($tags as &$tag) {
            if ($tag[0] === '01') {
                $tag[1] = '12';
                $hasPointOfInitiation = true;
            }
        }

        unset($tag);

        if (! $hasPointOfInitiation) {
            return null;
        }

        $insertAfter = self::value($tags, '53') !== null
            ? '53'
            : (self::value($tags, '52') !== null ? '52' : '01');

        $output = '';
        $inserted = false;

        foreach ($tags as $tag) {
            $output .= self::encode($tag[0], $tag[1]);

            if (! $inserted && $tag[0] === $insertAfter) {
                $output .= self::encode('54', $amount);
                $inserted = true;
            }
        }

        if (! $inserted) {
            return null;
        }

        $output .= '6304';

        return $output.self::crc16($output);
    }

    /**
     * Split a QRIS payload into its identifier/value pairs.
     *
     * @return list<array{0: string, 1: string}>
     */
    private static function parseTags(string $payload): array
    {
        $tags = [];
        $offset = 0;
        $length = strlen($payload);

        while ($offset < $length) {
            if ($offset + 4 > $length) {
                return [];
            }

            $id = substr($payload, $offset, 2);
            $size = substr($payload, $offset + 2, 2);

            if (! ctype_digit($id) || ! ctype_digit($size)) {
                return [];
            }

            $size = (int) $size;
            $offset += 4;

            if ($size < 0 || $offset + $size > $length) {
                return [];
            }

            $tags[] = [$id, substr($payload, $offset, $size)];
            $offset += $size;
        }

        return $tags;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $tags
     */
    private static function value(array $tags, string $id): ?string
    {
        foreach ($tags as $tag) {
            if ($tag[0] === $id) {
                return $tag[1];
            }
        }

        return null;
    }

    private static function encode(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    private static function normalizeAmount(string $amount): ?string
    {
        if (! is_numeric($amount)) {
            return null;
        }

        $value = number_format((float) $amount, 2, '.', '');
        $value = rtrim(rtrim($value, '0'), '.');

        if ($value === '' || (float) $value <= 0 || strlen($value) > 13) {
            return null;
        }

        return $value;
    }

    /**
     * CRC-16/CCITT-FALSE, as required by the QRIS specification.
     */
    private static function crc16(string $data): string
    {
        $crc = 0xFFFF;

        for ($index = 0; $index < strlen($data); $index++) {
            $crc ^= ord($data[$index]) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) !== 0
                    ? (($crc << 1) ^ 0x1021)
                    : ($crc << 1);

                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
