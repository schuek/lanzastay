<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Support\UserRole;

class OrderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (UserRole::isAdmin($user->role)) {
            return true;
        }

        return null;
    }

    public function viewKitchen(User $user): bool
    {
        return in_array($user->role, [UserRole::RECEPCION, ...UserRole::kitchenRoles()], true);
    }

    public function viewCleaningTasks(User $user): bool
    {
        return in_array($user->role, [UserRole::RECEPCION, UserRole::LIMPIEZA], true);
    }

    public function viewMaintenanceTasks(User $user): bool
    {
        return in_array($user->role, [UserRole::RECEPCION, UserRole::MANTENIMIENTO], true);
    }

    public function update(User $user, Order $order): bool
    {
        if ($user->role === UserRole::RECEPCION) {
            return in_array($order->service_type, ['limpieza', 'mantenimiento'], true);
        }

        return match ($order->service_type) {
            'comida' => in_array($user->role, UserRole::kitchenRoles(), true),
            'limpieza' => $user->role === UserRole::LIMPIEZA,
            'mantenimiento' => $user->role === UserRole::MANTENIMIENTO,
            default => false,
        };
    }

    public function poll(User $user, ?string $serviceType = null): bool
    {
        if ($user->role === UserRole::RECEPCION) {
            return true;
        }

        if ($serviceType === null) {
            return false;
        }

        return match ($serviceType) {
            'comida' => in_array($user->role, UserRole::kitchenRoles(), true),
            'limpieza' => $user->role === UserRole::LIMPIEZA,
            'mantenimiento' => $user->role === UserRole::MANTENIMIENTO,
            default => false,
        };
    }

    public function downloadKitchenInvoice(User $user, Order $order): bool
    {
        return $order->service_type === 'comida'
            && in_array($user->role, UserRole::kitchenRoles(), true);
    }
}
