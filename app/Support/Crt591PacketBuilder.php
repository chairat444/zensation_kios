<?php

namespace App\Support;

/**
 * CRT-591-M / CRT-591-M001 serial frame builder (Creator protocol).
 * BCC = XOR of every byte from STX (0xF2) through ETX (0x03), inclusive.
 *
 * @see CRT_591_M001_Protocol.pdf — send format §1.2.1, CARD MOVE §3.1.3, INITIALIZE §3.1.1
 */
class Crt591PacketBuilder
{
    public const STX = 0xF2;
    public const ETX = 0x03;
    public const CMT = 0x43; // 'C'

    /** Initialize ICRW (run after power-on; PM=33 = do not move card if inside). */
    public static function initializeNoMove(int $addr = 0): string
    {
        return self::build($addr, 0x30, 0x33, '');
    }

    /** Move card from stacker toward IC / read position (PM=31). */
    public static function cardMoveToIc(int $addr = 0): string
    {
        return self::build($addr, 0x32, 0x31, '');
    }

    /** Eject card out of the gate to the guest (PM=39). */
    public static function cardMoveOutGate(int $addr = 0): string
    {
        return self::build($addr, 0x32, 0x39, '');
    }

    public static function build(int $addr, int $cm, int $pm, string $data = ''): string
    {
        $addr &= 0x0F;
        $cm &= 0xFF;
        $pm &= 0xFF;

        $body = chr(self::CMT) . chr($cm) . chr($pm) . $data;
        $len = strlen($body);
        $lenH = ($len >> 8) & 0xFF;
        $lenL = $len & 0xFF;

        $frame = chr(self::STX)
            . chr($addr)
            . chr($lenH)
            . chr($lenL)
            . $body
            . chr(self::ETX);

        $bcc = 0;
        $n = strlen($frame);
        for ($i = 0; $i < $n; $i++) {
            $bcc ^= ord($frame[$i]);
        }

        return $frame . chr($bcc & 0xFF);
    }

    public static function toHex(string $bin): string
    {
        return strtoupper(bin2hex($bin));
    }
}
