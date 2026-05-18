<?php

namespace App\Support;

use App\Models\Habitacion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class GuestRoomResolver
{
    /**
     * Resuelve la habitación del huésped por access_token (URL/QR) y valida la sesión activa.
     */
    public static function fromAccessToken(string $accessToken, ?string $sessionToken = null): Habitacion
    {
        $habitacion = Habitacion::query()
            ->where('access_token', $accessToken)
            ->first();

        if (! $habitacion) {
            throw ValidationException::withMessages([
                'access_token' => 'Enlace de habitación no válido.',
            ]);
        }

        if ($habitacion->status !== 'ocupada') {
            throw ValidationException::withMessages([
                'access_token' => 'La habitación no está activa. Realiza check-in en recepción.',
            ]);
        }

        $expectedSession = (string) ($habitacion->current_session_token ?? '');
        if ($expectedSession === '') {
            throw ValidationException::withMessages([
                'session_token' => 'Sesión no disponible. Solicita acceso en recepción.',
            ]);
        }

        if ($sessionToken !== null && $sessionToken !== '' && ! hash_equals($expectedSession, $sessionToken)) {
            throw ValidationException::withMessages([
                'session_token' => 'Sesión inválida. Escanea de nuevo el QR de tu habitación.',
            ]);
        }

        return $habitacion;
    }

    public static function fromRequest(Request $request): Habitacion
    {
        $accessToken = (string) ($request->input('access_token') ?? $request->route('habitacion')?->access_token ?? '');

        if ($accessToken === '') {
            throw ValidationException::withMessages([
                'access_token' => 'Token de habitación requerido.',
            ]);
        }

        return self::fromAccessToken(
            $accessToken,
            (string) $request->input('session_token', ''),
        );
    }
}
