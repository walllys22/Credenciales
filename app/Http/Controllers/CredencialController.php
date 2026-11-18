<?php

namespace App\Http\Controllers;

use App\Models\{Inscripcion, Persona};
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Color\Alpha;
use BaconQrCode\Renderer\Color\Gray;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use App\Support\CredencialPublicUrl;

class CredencialController extends Controller
{
    public function show(Request $request, Persona $persona)
    {
        $request->validate(['inscripcion' => 'nullable|integer|min:1']);
        $persona->load(['grado', 'pais']);
        $inscripciones = $persona->inscripciones()->with('evento')->latest('id')->get();
        $inscripcion = $request->filled('inscripcion')
            ? $inscripciones->firstWhere('id', (int) $request->query('inscripcion'))
            : $inscripciones->first();
        if ($request->filled('inscripcion') && !$inscripcion) abort(404);

        return $this->render($persona, $inscripcion);
    }

    public function porInscripcion(Inscripcion $inscripcion)
    {
        $inscripcion->load(['persona.grado', 'persona.pais', 'evento']);

        return $this->render($inscripcion->persona, $inscripcion);
    }

    public function lote(Request $request)
    {
        $datos = $request->validate([
            'evento_id' => 'required|integer|exists:eventos,id',
            'inscripciones' => 'required|array|min:2|max:500',
            'inscripciones.*' => 'required|integer|distinct|exists:inscripciones,id',
        ]);
        $inscripciones = Inscripcion::with(['persona.grado', 'persona.pais', 'evento'])
            ->where('evento_id', $datos['evento_id'])->whereIn('id', $datos['inscripciones'])->get()->keyBy('id');
        if ($inscripciones->count() !== count($datos['inscripciones'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['inscripciones' => 'Las inscripciones deben pertenecer al evento seleccionado.']);
        }
        $credenciales = collect($datos['inscripciones'])->map(function ($id) use ($inscripciones) {
            $inscripcion = $inscripciones[$id];
            $generator = clone URL::getFacadeRoot();
            if ($publicUrl = CredencialPublicUrl::origin()) {
                $generator->forceRootUrl(rtrim($publicUrl, '/'));
                $generator->forceScheme(parse_url($publicUrl, PHP_URL_SCHEME));
            }
            $verificacionUrl = $generator->signedRoute('credenciales.verificar', ['inscripcion' => $id]);
            $fill = Fill::uniformColor(new Alpha(0, new Gray(100)), new Gray(0));
            $writer = new Writer(new ImageRenderer(new RendererStyle(300, 20, fill: $fill), new SvgImageBackEnd));
            $qr = 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($verificacionUrl));
            return compact('inscripcion', 'verificacionUrl', 'qr');
        });
        return response()->view('inscripciones.credenciales-lote', compact('credenciales'))
            ->header('Cache-Control', 'private, no-store');
    }

    private function render(Persona $persona, ?Inscripcion $inscripcion)
    {
        $qr = null;
        $verificacionUrl = null;
        if ($inscripcion) {
            $generator = clone URL::getFacadeRoot();
            if ($publicUrl = CredencialPublicUrl::origin()) {
                $generator->forceRootUrl(rtrim($publicUrl, '/'));
                $generator->forceScheme(parse_url($publicUrl, PHP_URL_SCHEME));
            }
            $verificacionUrl = $generator->signedRoute('credenciales.verificar', ['inscripcion' => $inscripcion->id]);
            // Genera el SVG con fondo transparente; el CSS no puede quitar el fondo interno del QR.
            $fill = Fill::uniformColor(new Alpha(0, new Gray(100)), new Gray(0));
            $writer = new Writer(new ImageRenderer(new RendererStyle(300, 20, fill: $fill), new SvgImageBackEnd));
            $qr = 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($verificacionUrl));
        }

        return response()->view('personas.vercred', compact('persona', 'inscripcion', 'qr', 'verificacionUrl'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function verificar(Inscripcion $inscripcion)
    {
        $inscripcion->load(['persona.grado', 'evento']);

        $persona = $inscripcion->persona;
        $evento = $inscripcion->evento;
        $fotoUrl = URL::signedRoute('credenciales.foto', ['inscripcion' => $inscripcion->id]);
        $eventoImagenUrl = URL::signedRoute('credenciales.imagen', ['inscripcion' => $inscripcion->id]);

        return response()->view('personas.verificarcred', compact('inscripcion', 'persona', 'evento', 'fotoUrl', 'eventoImagenUrl'))
            ->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow')->header('Referrer-Policy', 'no-referrer');
    }

    public function foto(Inscripcion $inscripcion)
    {
        return $this->imageResponse($inscripcion->persona->foto, 'persona-default.svg');
    }

    public function imagen(Inscripcion $inscripcion)
    {
        return $this->imageResponse($inscripcion->evento->imagen, 'evento-default.svg');
    }

    private function imageResponse(?string $path, string $fallback)
    {
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if (!$path || !Storage::disk('local')->exists($path)) {
            return response()->file(public_path('images/'.$fallback), $headers);
        }

        return Storage::disk('local')->response($path, null, $headers, 'inline');
    }
}
