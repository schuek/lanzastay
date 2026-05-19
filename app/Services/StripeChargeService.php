<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Charge;
use Stripe\Exception\CardException as StripeCardException;
use Stripe\Stripe;

class StripeChargeService
{
    public function charge(string $tokenId, float $amountEur, string $description): void
    {
        if ($amountEur <= 0) {
            throw ValidationException::withMessages([
                'total' => 'El importe no es válido.',
            ]);
        }

        $secret = (string) config('services.stripe.secret', '');

        if ($secret === '') {
            throw ValidationException::withMessages([
                'stripe_token' => 'El pago con tarjeta no está configurado. Elige efectivo o contacta con recepción.',
            ]);
        }

        Stripe::setApiKey($secret);

        try {
            Charge::create([
                'amount' => (int) round($amountEur * 100),
                'currency' => 'eur',
                'source' => $tokenId,
                'description' => $description,
            ]);
        } catch (StripeCardException $e) {
            throw ValidationException::withMessages([
                'stripe_token' => $e->getError()->message ?? 'No se pudo procesar el pago con tarjeta.',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe charge failed', ['error' => $e->getMessage()]);
            throw ValidationException::withMessages([
                'stripe_token' => 'No se pudo procesar el pago. Inténtalo de nuevo.',
            ]);
        }
    }
}
