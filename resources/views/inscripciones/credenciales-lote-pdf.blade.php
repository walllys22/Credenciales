@php
    $dataImage = function ($path, $mime) { return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path)); };
    $front = $dataImage(public_path('images/anverso.png'), 'image/png');
    $back = $dataImage(public_path('images/reverso.png'), 'image/png');
@endphp
<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
@page { margin: 10mm; }
body { margin:0; font-family: DejaVu Sans, sans-serif; color:#171717; }
.page { position:relative; width:190mm; height:274mm; page-break-after:always; }
.page.last { page-break-after:auto; }
.card { position:absolute; width:93mm; height:126mm; overflow:hidden; }
.art { position:absolute; left:0; top:0; width:93mm; height:126mm; }
.photo { position:absolute; left:7.5%; top:50.8%; width:37.8%; height:30.5%; }
.data { position:absolute; left:51%; width:42%; font-size:9.5pt; line-height:1.15; overflow-wrap:break-word; }
.name { top:52.8%; } .cargo { top:63%; } .grade { top:71.3%; } .document { top:81.8%; }
.country { position:absolute; left:7.5%; top:82.5%; width:37.8%; text-align:center; font-size:8.8pt; }
.country img { width:6.5mm; height:4.29mm; vertical-align:middle; }
.verification { position:absolute; top:35%; width:84%; left:8%; text-align:center; font-size:8.25pt; font-weight:bold; }
.qr { position:absolute; top:38.4%; left:30%; width:33mm; height:33mm; }
.event { position:absolute; top:63%; left:8%; width:84%; text-align:center; font-size:7.5pt; font-weight:bold; }
.dates { position:absolute; top:69%; left:8%; width:84%; text-align:center; font-size:6.75pt; line-height:1.4; }
.number { position:absolute; right:8%; bottom:3%; font-size:6pt; font-weight:bold; }
</style></head><body>
@foreach($credenciales->chunk(2) as $pagina)
<div class="page {{ $loop->last ? 'last' : '' }}">
@foreach($pagina as $credencial)
@php
    $inscripcion = $credencial['inscripcion']; $persona = $inscripcion->persona; $evento = $inscripcion->evento;
    $top = $loop->index * 130;
    $photoPath = $persona->foto && \Illuminate\Support\Facades\Storage::disk('local')->exists($persona->foto) ? \Illuminate\Support\Facades\Storage::disk('local')->path($persona->foto) : public_path('images/persona-default.svg');
    $photo = $dataImage($photoPath, mime_content_type($photoPath));
    $flag = $persona->pais?->bandera_url;
    $flagPath = $flag ? public_path('images/banderas/'.basename(parse_url($flag, PHP_URL_PATH))) : null;
@endphp
<div class="card" style="left:0;top:{{ $top }}mm">
    <img class="art" src="{{ $front }}" alt="">
    <img class="photo" src="{{ $photo }}" alt="">
    <div class="data name">{{ $persona->nombre_completo }}</div><div class="data cargo">{{ $inscripcion->cargo }}</div><div class="data grade">{{ $persona->grado->nombre }}</div><div class="data document">{{ $persona->documento_identidad }}</div>
    @if($persona->pais)<div class="country">@if($flagPath && is_file($flagPath))<img src="{{ $dataImage($flagPath, 'image/svg+xml') }}" alt=""> @endif{{ $persona->pais->nombre }}</div>@endif
    <span class="number">#{{ str_pad($inscripcion->id, 6, '0', STR_PAD_LEFT) }}</span>
</div>
<div class="card" style="left:97mm;top:{{ $top }}mm">
    <img class="art" src="{{ $back }}" alt="">
    <div class="verification">VERIFICACIÓN DIGITAL</div><img class="qr" src="{{ $credencial['qr'] }}" alt="QR">
    <div class="event">{{ $evento->nombre }}</div><div class="dates">{{ $evento->fecha_inicio->format('d/m/Y') }} al {{ $evento->fecha_final->format('d/m/Y') }}<br>{{ $evento->lugar }}<br>{{ $evento->ciudad }}</div>
    <span class="number">#{{ str_pad($inscripcion->id, 6, '0', STR_PAD_LEFT) }}</span>
</div>
@endforeach
</div>
@endforeach
</body></html>
