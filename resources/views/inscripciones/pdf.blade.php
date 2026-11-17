<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size:letter portrait; margin:10mm; }
        body { margin:0; font:8.5pt/1.2 Helvetica,Arial,sans-serif; color:#111; }
        header { position:fixed; top:0; left:0; right:0; height:48mm; text-align:center; }
        header img { width:100%; height:auto; }
        h2 { margin:2mm 0 3mm; font-size:16pt; }
        footer { position:fixed; bottom:6mm; left:0; right:0; border-top:1.5pt solid #111; text-align:center; font:bold 12pt/1.2 Times,serif; }
        footer .line { border-top:1pt solid #d00; margin-top:1mm; padding-top:2mm; }
        footer span,.country { color:#d00; }
        .sheet { padding-top:48mm; page-break-after:always; }
        .sheet:last-child { page-break-after:auto; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th,td { border:0.5pt solid #dce4ef; padding:1mm; vertical-align:middle; overflow-wrap:break-word; }
        thead th { font-size:8.45pt; background:#f0f4fa; text-align:left; height:8mm; }
        .report-participants td { height:10mm; }
        .report-number { font-size:9.5pt; }
        .report-participant-name { font-size:10pt; }
        .report-participant-organization { display:block; font-size:9.5pt; color:#647a9e; }
        .report-paid td { background:#ffff99; }
        .report-guest td { background:#f8d7da; }
        .text-center { text-align:center; }
        .report-payment-totals th,.report-payment-totals td { text-align:right; font-weight:bold; height:auto; padding:2mm 1mm; }
        .report-payment-totals th { background:transparent; }
        .report-debt-summary-row>td { border:0; padding:6mm 0 0; }
        .report-debt-summary { width:55%; margin:0; }
        .report-debt-summary th { width:60%; }
    </style>
</head>
<body>
<header>
    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/Cabecera.png'))) }}" alt="">
    <h2>Listado de Inscritos</h2>
</header>
<footer><div class="line">SEMINARIO INTERNACIONAL DE KARATE <span>&amp;</span> KOBUDO</div><div class="country">BOLIVIA 2026</div></footer>
@php
    $hojas = $inscripciones->chunk(15)->values();
    if ($hojas->isEmpty()) $hojas->push(collect());
    $resumenSeparado = $hojas->last()->count() > 10;
    if ($resumenSeparado) $hojas->push(collect());
@endphp
@foreach($hojas as $hoja)
<section class="sheet">
    <table>
        <colgroup><col style="width:6%"><col style="width:39%"><col style="width:13%"><col style="width:8%"><col style="width:11.33%"><col style="width:11.33%"><col style="width:11.34%"></colgroup>
        <thead><tr><th class="report-number">N.º</th><th>Nombre completo</th><th>Documento</th><th>Grado</th><th>Costo</th><th>Pagado</th><th>Saldo</th></tr></thead>
        <tbody class="report-participants">
            @if($hoja->isNotEmpty() || $inscripciones->isEmpty())
                @include('inscripciones.reporte-filas', ['inscripciones' => $hoja, 'numeroInicial' => $loop->index * 15])
            @endif
        </tbody>
        @if($loop->last)
            @include('inscripciones.reporte-totales')
        @endif
    </table>
</section>
@endforeach
</body>
</html>
