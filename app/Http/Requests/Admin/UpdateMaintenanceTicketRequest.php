<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        if (! $order instanceof Order || $order->service_type !== 'mantenimiento') {
            return false;
        }

        return $this->user()?->can('update', $order) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(['recibido', 'en_proceso', 'completado'])],
            'prioridad' => ['sometimes', Rule::in(['baja', 'media', 'alta'])],
            'description' => ['sometimes', 'string', 'max:2000'],
            'notas_internas' => ['nullable', 'string', 'max:5000'],
            'notas_resolucion' => [
                'nullable',
                'string',
                'max:5000',
                Rule::requiredIf(fn () => $this->input('status') === 'completado'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'notas_internas' => 'notas / instrucciones',
            'notas_resolucion' => 'notas de resolución (expediente)',
            'prioridad' => 'prioridad',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notas_resolucion.required' => 'Debes documentar la resolución en el expediente antes de cerrar la tarea.',
        ];
    }
}
