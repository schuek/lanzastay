<?php

namespace App\Support;

final class UserRole
{
    public const ADMIN = 'admin';

    public const RECEPCION = 'recepcion';

    public const COCINA = 'cocina';

    public const ROOM_SERVICE = 'room_service';

    public const LIMPIEZA = 'limpieza';

    public const MANTENIMIENTO = 'mantenimiento';

    /** @return list<string> */
    public static function kitchenRoles(): array
    {
        return [self::COCINA, self::ROOM_SERVICE];
    }

    public static function isAdmin(string $role): bool
    {
        return $role === self::ADMIN;
    }

    /** @return list<string> */
    public static function assignableRoles(): array
    {
        return [
            self::ADMIN,
            self::RECEPCION,
            self::COCINA,
            self::ROOM_SERVICE,
            self::LIMPIEZA,
            self::MANTENIMIENTO,
        ];
    }

    /**
     * Opciones para selectores de gestión de personal (value + label).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function managementRoleOptions(): array
    {
        return [
            ['value' => self::ADMIN, 'label' => 'Administrador'],
            ['value' => self::RECEPCION, 'label' => 'Recepción'],
            ['value' => self::COCINA, 'label' => 'Cocina'],
            ['value' => self::ROOM_SERVICE, 'label' => 'Room service'],
            ['value' => self::LIMPIEZA, 'label' => 'Limpieza'],
            ['value' => self::MANTENIMIENTO, 'label' => 'Mantenimiento'],
        ];
    }
}
