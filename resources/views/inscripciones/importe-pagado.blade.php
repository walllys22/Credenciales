@if($inscripcion->pagado_bs > 0 || !($inscripcion->pagado_usd > 0))
Bs {{ number_format($inscripcion->pagado_bs ?? 0, 2, ',', '.') }}
@endif
@if($inscripcion->pagado_usd > 0)
@if($inscripcion->pagado_bs > 0) + @endif
$us {{ number_format($inscripcion->pagado_usd, 2, ',', '.') }}
@endif
