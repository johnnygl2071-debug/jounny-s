<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudRequest;
use App\Models\Apoyo;
use App\Models\EventoComedor;
use App\Models\Queja;
use App\Models\RegistroApoyo;
use App\Services\CitaService;
use App\Services\ComedorService;
use App\Services\SeguimientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SolicitudController extends Controller
{
    public function create(string $module): View
    {
        $definition = config('samuel.modules.'.$module);
        abort_unless($definition, 404);
        $options = match ($module) {
            'apoyos' => Apoyo::where('activo', true)->orderByDesc('created_at')->get(),
            'comedor' => EventoComedor::where('activo', true)->where('fecha', '>=', today()->toDateString())->withSum(['registros as personas_ocupadas' => fn ($query) => $query->where('estatus', '!=', 'cancelado')], 'numero_personas')->orderBy('fecha')->orderBy('hora_inicio')->get(),
            default => collect(),
        };

        $selectedEvent = null;
        if ($module === 'comedor') {
            $requestedEvent = old('evento_id', request('evento'));
            $selectedEvent = $options->first(fn ($event) => $event->id == $requestedEvent
                && ((int) $event->cupo_maximo === 0 || (int) $event->personas_ocupadas < (int) $event->cupo_maximo))?->id;
        }

        return view('solicitudes.create', compact('module', 'definition', 'options', 'selectedEvent'));
    }

    public function store(SolicitudRequest $request, string $module, CitaService $citas, ComedorService $comedor): RedirectResponse
    {
        $key = 'solicitud:'.$module.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['solicitud' => 'Ya registraste tres solicitudes en la última hora. Intenta más tarde.']);
        }
        RateLimiter::hit($key, 3600);
        $data = array_map(fn ($value) => $value ?? '', $request->safe()->except('evidencia'));
        $path = null;
        try {
            if ($module === 'quejas') {
                $path = $request->file('evidencia')->store('evidencias', 'local');
                if (! $path) {
                    throw ValidationException::withMessages(['evidencia' => 'No fue posible guardar la evidencia.']);
                }
            }
            $record = match ($module) {
                'quejas' => Queja::create([...$data, 'evidencia_path' => $path, 'estatus' => 'pendiente']),
                'citas' => $citas->reserve($data),
                'apoyos' => RegistroApoyo::create([...$data, 'ine_path' => null, 'estatus' => 'pendiente']),
                'comedor' => $comedor->reserve($data),
            };
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return to_route('solicitudes.create', $module)->with('success', 'Solicitud registrada.')->with('folio', SeguimientoService::folio($module, $record->id));
    }

    public function availability(Request $request, CitaService $service): JsonResponse
    {
        $data = $request->validate(['fecha' => ['required', 'date_format:Y-m-d', 'after:today']]);
        if (! $request->date('fecha')->isTuesday()) {
            throw ValidationException::withMessages(['fecha' => 'Selecciona un martes posterior a hoy.']);
        }

        return response()->json(['disponibles' => $service->available($data['fecha'])]);
    }
}
