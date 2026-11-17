<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class ReportePdf
{
    public function generar(string $html): string
    {
        $browser = $this->navegador();
        $directory = storage_path('app/private/reportes-pdf/'.Str::uuid());
        File::ensureDirectoryExists($directory);
        try {
            // El navegador sólo recibe el HTML generado por Blade y recursos locales.
            $public = 'file:///'.ltrim(str_replace('\\', '/', public_path()), '/').'/';
            $html = str_replace(rtrim(asset('/'), '/').'/', $public, $html);
            File::put($directory.'/reporte.html', $html);
            $process = new Process([
                $browser, '--headless=new', '--no-first-run', '--no-sandbox',
                '--disable-gpu', '--disable-gpu-sandbox', '--disable-software-rasterizer',
                '--disable-background-networking', '--host-resolver-rules=MAP * ~NOTFOUND',
                '--allow-file-access-from-files', '--no-pdf-header-footer',
                '--user-data-dir='.$directory.'/perfil',
                '--print-to-pdf='.$directory.'/reporte.pdf', '--virtual-time-budget=5000',
                'file:///'.ltrim(str_replace('\\', '/', $directory), '/').'/reporte.html',
            ]);
            $process->setTimeout(60);
            $process->run();
            if (! is_file($directory.'/reporte.pdf')) {
                throw new RuntimeException('El navegador no pudo generar el reporte PDF.');
            }
            $pdf = File::get($directory.'/reporte.pdf');
            if (! str_starts_with($pdf, '%PDF-')) {
                throw new RuntimeException('El archivo generado no es un PDF válido.');
            }

            return $pdf;
        } finally {
            // La respuesta conserva los bytes; no quedan documentos personales temporales.
            File::deleteDirectory($directory);
        }
    }

    private function navegador(): string
    {
        $candidates = array_filter([
            config('reportes.navegador'),
            getenv('LOCALAPPDATA').'/Google/Chrome/Application/chrome.exe',
            getenv('PROGRAMFILES').'/Google/Chrome/Application/chrome.exe',
            getenv('PROGRAMFILES(X86)').'/Microsoft/Edge/Application/msedge.exe',
            '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser',
        ]);
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        throw new RuntimeException('Configure REPORT_PDF_BROWSER con la ruta de Chrome o Edge para descargar el PDF.');
    }
}
