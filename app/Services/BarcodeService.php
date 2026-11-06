<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeService
{
    /** Inline SVG for a code: EAN-13 when it is a valid 13-digit number, otherwise Code 128. */
    public static function svg(string $code, int $height = 50, int $width = 2): string
    {
        $g = new BarcodeGeneratorSVG();
        $type = preg_match('/^\d{13}$/', $code) ? $g::TYPE_EAN_13 : $g::TYPE_CODE_128;
        try {
            return $g->getBarcode($code, $type, $width, $height);
        } catch (\Throwable) {
            return $g->getBarcode($code, $g::TYPE_CODE_128, $width, $height);
        }
    }
}
