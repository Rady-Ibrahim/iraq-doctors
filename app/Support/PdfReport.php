<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfReport
{
    public static function download(string $view, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download($filename);
    }

    public static function formatMoney(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 0).' د.ع';
    }
}
