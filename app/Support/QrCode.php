<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode as Generator;
use chillerlan\QRCode\QROptions;

/**
 * QR-codes als inline SVG (aanleverbewijs, later cadeaubonnen). Gebruikt de generator die
 * Filament al meebrengt voor de tweestapsverificatie.
 */
final class QrCode
{
    public static function svg(string $data, int $quietZone = 2): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => $quietZone,
            'drawLightModules' => false,
        ]);

        return (new Generator($options))->render($data);
    }
}
