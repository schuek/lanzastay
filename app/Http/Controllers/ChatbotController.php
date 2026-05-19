<?php

namespace App\Http\Controllers;

use App\Models\Habitacion;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    private const GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:8000'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'access_token' => ['nullable', 'uuid', 'exists:habitacions,access_token'],
        ]);

        try {
            $apiKey = env('GEMINI_API_KEY');
            if ($apiKey === null || trim((string) $apiKey) === '') {
                Log::error('Chatbot: GEMINI_API_KEY vacía o no definida');

                return response()->json([
                    'reply' => 'El asistente no está configurado. Usa el botón de ayuda para contactar con recepción.',
                ], 200);
            }

            $habitacion = $this->resolveHabitacion($validated);
            $numeroHabitacion = $habitacion?->numero ?? 'desconocida';
            $listaMenu = $this->buildMenuCatalogText();

            $locale = $this->resolveGuestLocale($request);
            $instruccionesSistema = $this->buildMasterPrompt($habitacion, $numeroHabitacion, $listaMenu, $locale);

            $url = self::GEMINI_URL.'?key='.urlencode($apiKey);

            $promptCompleto = $instruccionesSistema."\n\n---\n\nMensaje del huésped:\n".$validated['message'];

            $response = Http::timeout(60)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $promptCompleto],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $texto = $response->json('candidates.0.content.parts.0.text');

                if (! is_string($texto) || $texto === '') {
                    Log::error('Chatbot: respuesta Gemini sin texto utilizable: '.$response->body());

                    return response()->json([
                        'reply' => 'Ahora mismo nuestros circuitos están saturados. Vuelve a intentarlo en unos minutos o pregunta en recepción.',
                    ], 200);
                }

                return response()->json(['reply' => $texto], 200);
            }

            Log::error('Error en Gemini: '.$response->body());

            return response()->json([
                'reply' => 'Ahora mismo nuestros circuitos están saturados. Vuelve a intentarlo en unos minutos o pregunta en recepción.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Chatbot: excepción en sendMessage: '.$e->getMessage());

            return response()->json([
                'reply' => 'Ha ocurrido un fallo técnico al conectar con el asistente. Por favor, inténtalo más tarde o contacta con recepción.',
            ], 200);
        }
    }

    private function resolveHabitacion(array $validated): ?Habitacion
    {
        if (! empty($validated['access_token'])) {
            return Habitacion::query()
                ->where('access_token', $validated['access_token'])
                ->first();
        }

        if (! empty($validated['room_number'])) {
            return Habitacion::query()
                ->where('numero', $validated['room_number'])
                ->first();
        }

        return null;
    }

    private function buildMenuCatalogText(): string
    {
        $services = Service::query()
            ->where(function ($query) {
                $query->where('service_type', 'comida')
                    ->orWhereIn('service_category', ['comida', 'bebida', 'postre']);
            })
            ->orderBy('service_category')
            ->orderBy('name')
            ->get();

        if ($services->isEmpty()) {
            $services = Service::query()->orderBy('name')->get();
        }

        if ($services->isEmpty()) {
            return 'No hay platos ni productos cargados en la carta en este momento.';
        }

        return $services
            ->map(function (Service $service): string {
                $precio = number_format((float) $service->price, 2, ',', '.');
                $categoria = $service->service_category
                    ?? $service->categoria_restaurante
                    ?? 'general';
                $linea = sprintf('- %s (%s): %s €', $service->name, $categoria, $precio);

                if (! empty($service->description)) {
                    $linea .= ' — '.Str::limit($service->description, 100);
                }

                return $linea;
            })
            ->implode("\n");
    }

    private function resolveGuestLocale(Request $request): string
    {
        $locale = app()->getLocale();

        if (in_array($locale, ['es', 'en', 'fr', 'de'], true)) {
            return $locale;
        }

        $header = $request->header('Accept-Language');
        if (is_string($header) && $header !== '') {
            $candidate = strtolower(trim(explode(',', $header)[0]));
            $candidate = strtolower(trim(explode(';', $candidate)[0]));
            if (in_array($candidate, ['es', 'en', 'fr', 'de'], true)) {
                return $candidate;
            }
        }

        return 'es';
    }

    private function buildMasterPrompt(?Habitacion $habitacion, string $numeroHabitacion, string $listaMenu, string $locale): string
    {
        $menuUrl = $habitacion?->generateQrUrl() ?: '/menu';

        return <<<PROMPT
Eres El Asistente 360 del hotel LanzaStay. Estás hablando con el huésped de la HABITACIÓN {$numeroHabitacion}.
Tienes acceso a esta carta de comida:
{$listaMenu}
REGLAS IMPORTANTES:

REGLA DE IDIOMA (ESTRICTA): El usuario tiene su interfaz configurada en el idioma con código [{$locale}]. DEBES responder SIEMPRE en ese idioma, sin excepción. No cambies de idioma aunque el huésped escriba en otro.

Si el huésped quiere pedir comida, no puedes pedirla tú por él. Debes darle las instrucciones y decirle que vaya a la sección de pedidos usando el enlace: {$menuUrl}.

Si preguntan por excursiones (como Timanfaya, Jameos del Agua), dales información breve y diles que pueden reservar u obtener más detalles en la sección de Turismo del panel o visitando cactlanzarote.com.

Si piden algo a recepción (toallas, limpieza), confirma que has pasado el aviso (aunque sea simulado).

Sé siempre muy amable, conciso y profesional.
PROMPT;
    }
}
