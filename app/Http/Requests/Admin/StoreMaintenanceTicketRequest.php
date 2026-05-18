<?php

namespace App\Http\Requests\Admin;

use App\Support\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return UserRole::isAdmin($user->role)
            || in_array($user->role, [UserRole::RECEPCION, UserRole::MANTENIMIENTO], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'habitacion_id' => ['required', 'integer', 'exists:habitacions,id'],
            'description' => ['required', 'string', 'max:2000'],
            'notas_internas' => ['nullable', 'string', 'max:5000'],
            'prioridad' => ['nullable', Rule::in(['baja', 'media', 'alta'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'habitacion_id' => 'habitación',
            'description' => 'descripción del problema',
            'notas_internas' => 'notas / instrucciones',
            'prioridad' => 'prioridad',
        ];
    }
}
