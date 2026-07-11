<?php

class QrisGenerator {
    // The base static QRIS string decoded from the user's uploaded QR code
    private static $baseQris = '00020101021126610014COM.GO-JEK.WWW01189360091436855577190210G6855577190303UMI51440014ID.CO.QRIS.WWW0215ID10253711800720303UMI5204490053033605802ID5915maju, BKS UTARA6011KOTA BEKASI61051714262140703A01110362163042B0A';

    /**
     * Generate a Dynamic QRIS string with the given nominal amount
     */
    public static function generateDynamic($amount) {
        $qris = self::$baseQris;
        
        // 1. Change Point of Initiation Method from Static (11) to Dynamic (12)
        $qris = str_replace('010211', '010212', $qris);
        
        // 2. Remove the old CRC (last 4 characters)
        // Tag 63 Length 04 is the last 8 characters "6304XXXX", but we need to recalculate the CRC for everything up to "6304"
        $qrisWithoutCrc = substr($qris, 0, -4);
        
        // Wait, we need to inject the amount (Tag 54) before Tag 58 (Country Code) or right after Tag 53 (Currency)
        // Let's find Tag 53 "5303360"
        $currencyTag = '5303360';
        $insertPos = strpos($qrisWithoutCrc, $currencyTag);
        
        if ($insertPos !== false) {
            $insertPos += strlen($currencyTag); // Position right after Currency Tag
            
            // Format the amount
            $amountStr = (string)$amount;
            $amountLen = str_pad(strlen($amountStr), 2, '0', STR_PAD_LEFT);
            $tag54 = '54' . $amountLen . $amountStr;
            
            // Reconstruct the string: Left part + Tag54 + Right part (minus old CRC)
            $leftPart = substr($qrisWithoutCrc, 0, $insertPos);
            // Right part actually currently ends with "6304", which is good, we keep that for calculating new CRC
            $rightPart = substr($qrisWithoutCrc, $insertPos);
            
            $newQrisBase = $leftPart . $tag54 . $rightPart;
            
            // Calculate new CRC16 CCITT
            $newCrc = self::calculateCRC16($newQrisBase);
            
            return $newQrisBase . $newCrc;
        }
        
        // Fallback to static if tag not found
        return self::$baseQris;
    }

    /**
     * Calculate CRC16 CCITT (0xFFFF)
     */
    private static function calculateCRC16($str) {
        $crc = 0xFFFF;
        for ($c = 0; $c < strlen($str); $c++) {
            $crc ^= (ord($str[$c]) << 8);
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc = $crc << 1;
                }
            }
        }
        $hex = strtoupper(dechex($crc & 0xFFFF));
        return str_pad($hex, 4, '0', STR_PAD_LEFT);
    }
}
