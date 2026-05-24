<?php

namespace App\Support;

/**
 * Parse Xeeder PMSif V1.723 card payload (pipe-delimited).
 *
 * Read response (PMSif_TCP_Demo): 000000|M15A693C3|VD|R201|T04|D202605191554|O202605221800|Nempty|
 * Legacy write format:           00000I|R1001|T04|D201705021723|O201705051800
 */
class HotelLockCardParser
{
    public static function parse(string $raw): array
    {
        $result = [
            'raw' => $raw,
            'status' => null,
            'card_type' => null,
            'machine_id' => null,
            'room' => null,
            'guest_type' => null,
            'guest_name' => null,
            'check_in' => null,
            'check_out' => null,
            'common_doors' => null,
            'elevators' => null,
            'is_empty' => false,
            'is_checkout' => false,
        ];

        if ($raw === '00000E') {
            $result['is_empty'] = true;
            $result['card_type'] = 'empty';

            return $result;
        }

        if ($raw === '00000B') {
            $result['is_checkout'] = true;
            $result['card_type'] = 'checkout';

            return $result;
        }

        if (!str_contains($raw, '|')) {
            if (str_starts_with($raw, '00000')) {
                $result['card_type'] = $raw;
            }

            return $result;
        }

        $parts = array_values(array_filter(explode('|', $raw), fn ($p) => $p !== ''));
        if ($parts !== []) {
            $result['status'] = $parts[0];
            $result['card_type'] = $parts[0];
        }

        foreach (array_slice($parts, 1) as $part) {
            if ($part === 'VD') {
                $result['vd'] = true;
                continue;
            }
            if (strlen($part) < 2) {
                continue;
            }
            $tag = $part[0];
            $value = substr($part, 1);
            match ($tag) {
                'M' => $result['machine_id'] = $value,
                'R' => $result['room'] = $value,
                'T' => $result['guest_type'] = $value,
                'D' => $result['check_in'] = $value,
                'O' => $result['check_out'] = $value,
                'C' => $result['common_doors'] = $value,
                'L' => $result['elevators'] = $value,
                'N' => $result['guest_name'] = $value,
                default => null,
            };
            if ($tag === 'N' && strcasecmp($value, 'empty') === 0) {
                $result['is_empty'] = true;
            }
        }

        return $result;
    }

    /** Approximate 00000I write string from read fields (for comparison with external system). */
    public static function buildWriteCommand(array $parsed): ?string
    {
        if (!empty($parsed['is_empty']) || !empty($parsed['is_checkout']) || empty($parsed['room'])) {
            return null;
        }

        $parts = ['00000I', 'R' . $parsed['room']];
        $gt = $parsed['guest_type'] ?? null;
        if ($gt !== null && $gt !== '') {
            $parts[] = strtoupper((string) $gt)[0] === 'T' ? (string) $gt : 'T' . $gt;
        }
        if (!empty($parsed['check_in'])) {
            $parts[] = 'D' . $parsed['check_in'];
        }
        if (!empty($parsed['check_out'])) {
            $parts[] = 'O' . $parsed['check_out'];
        }
        if (!empty($parsed['common_doors'])) {
            $parts[] = 'C' . $parsed['common_doors'];
        }
        if (!empty($parsed['elevators'])) {
            $parts[] = 'L' . $parsed['elevators'];
        }

        return implode('|', $parts);
    }

    /** Format D201705021723 -> 02 May 2017 17:23 (spec uses YmdHi). */
    public static function formatLockDateTime(?string $ymdHi): ?string
    {
        if ($ymdHi === null || strlen($ymdHi) < 12) {
            return null;
        }

        $dt = \DateTime::createFromFormat('YmdHi', substr($ymdHi, 0, 12));

        return $dt ? $dt->format('d M Y H:i') : $ymdHi;
    }
}
