<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;

class ReportePdf
{
    public function generar(array $datos): string
    {
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setChroot(public_path());
        $pdf = new Dompdf($options);
        $pdf->setPaper('letter', 'portrait');
        $pdf->loadHtml(view('inscripciones.pdf', $datos)->render(), 'UTF-8');
        $pdf->render();
        $pdf->getCanvas()->page_script(function ($page, $pages, $canvas, $fonts) {
            $font = $fonts->getFont('Helvetica');
            $text = "Página $page de $pages";
            $width = $fonts->getTextWidth($text, $font, 8);
            $canvas->text(612 - 28.3465 - $width, 792 - 28.3465 - 8, $text, $font, 8);
        });

        return $pdf->output();
    }
}
