<?php

namespace App\Support;

use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfReport
{
    public static function download(string $view, array $data, string $filename): Response
    {
        $html = view($view, $data)->render();
        $html = self::shapeArabic($html);

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download($filename);
    }

    /**
     * DomPDF does not support Arabic bidi/shaping; reshape Arabic runs for LTR rendering.
     */
    public static function shapeArabic(string $html): string
    {
        $arabic = new Arabic;
        $positions = $arabic->arIdentify($html);

        for ($i = count($positions) - 1; $i >= 1; $i -= 2) {
            $start = $positions[$i - 1];
            $length = $positions[$i] - $start;
            $segment = substr($html, $start, $length);
            $shaped = $arabic->utf8Glyphs($segment, 600, false);
            $html = substr_replace($html, $shaped, $start, $length);
        }

        return $html;
    }

    public static function formatMoney(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 0).' د.ع';
    }

    public static function formatFileSize(int|string|null $bytes): string
    {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '-';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 1).' '.($units[$i] ?? '');
    }
}
