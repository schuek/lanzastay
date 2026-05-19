<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityReservation;
use App\Models\Habitacion;
use App\Services\StripeChargeService;
use App\Support\GuestRoomResolver;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function __construct(
        private readonly StripeChargeService $stripeCharge,
    ) {}

    /**
     * Crea una reserva de actividad para el huésped autenticado por access_token + session_token.
     */
    public function store(Request $request): JsonResponse
    {
        $activityId = $request->input('activity_id') ?? $request->input('actividad_id');

        if (! $activityId) {
            return $this->errorResponse('Falta el identificador de actividad.', 422);
        }

        try {
            $room = GuestRoomResolver::fromRequest($request);
            $sessionToken = $this->resolveSessionToken($request, $room->current_session_token);
            $plazasSolicitadas = $this->resolvePlazasSolicitadas($request);
            $scheduledTime = $this->resolveScheduledTime($request);

            $reservation = DB::transaction(function () use (
                $request,
                $activityId,
                $room,
                $sessionToken,
                $plazasSolicitadas,
                $scheduledTime
            ): ActivityReservation {
                /** @var Activity|null $activity */
                $activity = Activity::query()
                    ->lockForUpdate()
                    ->find((int) $activityId);

                if (! $activity) {
                    throw ValidationException::withMessages([
                        'activity_id' => 'Actividad no encontrada.',
                    ]);
                }

                $plazasDisponibles = $activity->plazas_disponibles;

                Log::info('Reserva actividad: comprobación de cupo', [
                    'activity_id' => $activity->id,
                    'plazas_solicitadas' => $plazasSolicitadas,
                    'plazas_disponibles' => $plazasDisponibles,
                    'scheduled_time' => $scheduledTime,
                    'room_id' => $room->id,
                ]);

                $this->assertPlazasDisponibles($activity, $plazasSolicitadas, $plazasDisponibles);

                $totalPrice = round((float) $activity->price * $plazasSolicitadas, 2);
                $paymentMethod = $this->resolvePaymentMethod($request, $totalPrice);
                $resolvedPaymentMethod = $this->resolvePaymentForActivity(
                    $request,
                    $activity,
                    $room,
                    $totalPrice,
                    $paymentMethod,
                );

                return ActivityReservation::query()->create(
                    $this->buildReservationAttributes(
                        $room,
                        $activity,
                        $sessionToken,
                        $plazasSolicitadas,
                        $scheduledTime,
                        $totalPrice,
                        $resolvedPaymentMethod,
                    ),
                );
            });

            Log::info('Reserva actividad: creada correctamente', [
                'reservation_id' => $reservation->id,
                'activity_id' => $reservation->activity_id,
                'room_id' => $reservation->room_id,
                'payment_method' => $reservation->payment_method,
            ]);

            return response()->json([
                'success' => true,
                'reservation' => $this->formatReservationForApi($reservation),
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse(
                collect($e->errors())->flatten()->first() ?? 'No se pudo validar la reserva.',
                422,
                $e->errors(),
            );
        } catch (QueryException $e) {
            Log::error('Reserva actividad: error de base de datos', [
                'message' => $e->getMessage(),
                'activity_id' => $activityId,
            ]);

            return $this->errorResponse($this->friendlyDatabaseErrorMessage($e), $this->databaseErrorStatus($e));
        } catch (\Throwable $e) {
            Log::error('Reserva actividad: error inesperado', [
                'message' => $e->getMessage(),
                'activity_id' => $activityId,
            ]);

            return $this->errorResponse(
                'No se pudo completar la reserva. Inténtalo de nuevo o contacta con recepción.',
                500,
            );
        }
    }

    /**
     * Respuesta JSON ligera (evita accessors pesados del modelo Activity en producción).
     *
     * @return array<string, mixed>
     */
    private function formatReservationForApi(ActivityReservation $reservation): array
    {
        $reservation->loadMissing(['activity', 'room']);

        $activity = $reservation->activity;
        $room = $reservation->room;

        return [
            'id' => $reservation->id,
            'room_id' => $reservation->room_id,
            'activity_id' => $reservation->activity_id,
            'seats_booked' => $reservation->seats_booked,
            'scheduled_time' => $reservation->scheduled_time,
            'total_price' => $reservation->total_price,
            'payment_method' => $reservation->payment_method,
            'status' => $reservation->status,
            'created_at' => $reservation->created_at,
            'updated_at' => $reservation->updated_at,
            'activity' => $activity ? [
                'id' => $activity->id,
                'name' => $activity->name,
                'type' => $activity->type,
                'price' => $activity->price,
                'image_url' => $activity->image_url,
                'date_time' => $activity->date_time,
            ] : null,
            'room' => $room ? [
                'id' => $room->id,
                'numero' => $room->numero,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReservationAttributes(
        Habitacion $room,
        Activity $activity,
        string $sessionToken,
        int $plazasSolicitadas,
        string $scheduledTime,
        float $totalPrice,
        ?string $resolvedPaymentMethod,
    ): array {
        $attributes = [
            'room_id' => $room->id,
            'session_token' => $sessionToken,
            'activity_id' => $activity->id,
            'seats_booked' => $plazasSolicitadas,
            'total_price' => $totalPrice,
            'status' => 'pendiente',
        ];

        if (Schema::hasColumn('activity_reservations', 'scheduled_time')) {
            $attributes['scheduled_time'] = $scheduledTime;
        }

        if (Schema::hasColumn('activity_reservations', 'payment_method')) {
            $attributes['payment_method'] = $resolvedPaymentMethod ?? ($totalPrice > 0 ? 'efectivo' : null);
        }

        return $attributes;
    }

    private function resolvePaymentMethod(Request $request, float $totalPrice): ?string
    {
        if ($totalPrice <= 0) {
            return null;
        }

        $raw = $request->input('payment_method');
        $method = strtolower(trim((string) ($raw ?: 'efectivo')));

        if (! in_array($method, ['tarjeta', 'efectivo'], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Selecciona un método de pago válido (tarjeta o efectivo).',
            ]);
        }

        return $method;
    }

    /**
     * @throws ValidationException
     */
    private function resolvePaymentForActivity(
        Request $request,
        Activity $activity,
        Habitacion $room,
        float $totalPrice,
        ?string $paymentMethod,
    ): ?string {
        if ($totalPrice <= 0) {
            return null;
        }

        if ($paymentMethod === null) {
            throw ValidationException::withMessages([
                'payment_method' => 'Debes elegir cómo pagar la actividad.',
            ]);
        }

        if ($paymentMethod === 'tarjeta') {
            $token = (string) ($request->input('stripe_token') ?? '');

            if ($token === '') {
                throw ValidationException::withMessages([
                    'stripe_token' => 'Debes introducir los datos de la tarjeta.',
                ]);
            }

            $this->assertStripeConfigured();

            $this->stripeCharge->charge(
                $token,
                $totalPrice,
                'LANZASTAY reserva actividad '.$activity->name.' habitación '.$room->numero,
            );
        }

        return $paymentMethod;
    }

    private function assertStripeConfigured(): void
    {
        if (blank(config('services.stripe.secret'))) {
            throw ValidationException::withMessages([
                'stripe_token' => 'El pago con tarjeta no está disponible en este momento. Elige efectivo o contacta con recepción.',
            ]);
        }
    }

    private function resolvePlazasSolicitadas(Request $request): int
    {
        $raw = $request->input('plazas')
            ?? $request->input('seats_booked')
            ?? $request->input('cantidad')
            ?? $request->input('num_personas')
            ?? $request->input('guests')
            ?? 1;

        $plazas = (int) $raw;

        if ($plazas < 1) {
            throw ValidationException::withMessages([
                'plazas' => 'Debes reservar al menos 1 plaza.',
                'seats_booked' => 'Debes reservar al menos 1 plaza.',
            ]);
        }

        return $plazas;
    }

    private function resolveScheduledTime(Request $request): string
    {
        $raw = trim((string) ($request->input('scheduled_time') ?? $request->input('hora') ?? ''));

        if ($raw === '') {
            throw ValidationException::withMessages([
                'scheduled_time' => 'Debes seleccionar una hora para la actividad.',
                'hora' => 'Debes seleccionar una hora para la actividad.',
            ]);
        }

        if (! preg_match('/^\d{2}:\d{2}$/', $raw)) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'Formato de hora no válido.',
                'hora' => 'Formato de hora no válido.',
            ]);
        }

        [$hour, $minute] = array_map('intval', explode(':', $raw));

        if ($minute !== 0 || $hour < 9 || $hour >= 18) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'La hora debe estar entre las 09:00 y las 18:00.',
                'hora' => 'La hora debe estar entre las 09:00 y las 18:00.',
            ]);
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function resolveSessionToken(Request $request, ?string $expectedToken): string
    {
        $sessionToken = (string) $request->input('session_token', '');
        $expected = (string) ($expectedToken ?? '');

        if ($expected === '') {
            throw ValidationException::withMessages([
                'session_token' => 'Sesión no disponible. Solicita acceso en recepción.',
            ]);
        }

        if ($sessionToken === '' || ! hash_equals($expected, $sessionToken)) {
            throw ValidationException::withMessages([
                'session_token' => 'Sesión inválida. Escanea de nuevo el QR de tu habitación.',
            ]);
        }

        return strlen($sessionToken) > 80 ? substr($sessionToken, 0, 80) : $sessionToken;
    }

    /**
     * @throws ValidationException
     */
    private function assertPlazasDisponibles(Activity $activity, int $plazasSolicitadas, ?int $plazasDisponibles): void
    {
        if (! $activity->tieneAforoLimitado() || $plazasDisponibles === null) {
            return;
        }

        if ($plazasSolicitadas > $plazasDisponibles) {
            $mensaje = 'Lo sentimos, no quedan suficientes plazas para esta hora.';

            throw ValidationException::withMessages([
                'plazas' => $mensaje,
                'seats_booked' => $mensaje,
            ]);
        }
    }

    private function friendlyDatabaseErrorMessage(QueryException $e): string
    {
        $code = (int) ($e->errorInfo[1] ?? 0);

        if ($code === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
            return 'El servicio no está disponible temporalmente. Contacta con recepción.';
        }

        if ($code === 2002 || str_contains($e->getMessage(), 'Connection refused')) {
            return 'No hay conexión con la base de datos. Inténtalo en unos minutos.';
        }

        if ($code === 1452 || str_contains($e->getMessage(), 'foreign key constraint')) {
            return 'La actividad o la habitación ya no son válidas. Recarga el menú e inténtalo de nuevo.';
        }

        if ($code === 1054 || str_contains($e->getMessage(), 'Unknown column')) {
            Log::critical('Reserva actividad: migración pendiente', ['error' => $e->getMessage()]);

            return 'El sistema está en mantenimiento. Contacta con recepción.';
        }

        return 'No se pudo completar la reserva. Inténtalo de nuevo o contacta con recepción.';
    }

    private function databaseErrorStatus(QueryException $e): int
    {
        $code = (int) ($e->errorInfo[1] ?? 0);

        return in_array($code, [1049, 2002, 1452, 1054], true) ? 422 : 500;
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    private function errorResponse(string $message, int $status, ?array $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
