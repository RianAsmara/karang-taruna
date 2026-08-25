<?php

namespace App\Support;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Response;

/**
 * Shared by the web and API report controllers so the QR target, size,
 * and margin used on a physical notice board stay identical everywhere.
 */
class ReportQrCode
{
    public static function png(string $url): Response
    {
        $result = (new Builder(writer: new PngWriter))->build(
            data: $url,
            size: 320,
            margin: 12,
        );

        return response($result->getString(), 200)->header('Content-Type', $result->getMimeType());
    }
}
