<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_recepcion_cannot_access_personal_management(): void
    {
        $user = User::factory()->role(UserRole::RECEPCION)->create();

        $this->actingAs($user)->get(route('admin.personal'))->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_employee(): void
    {
        $admin = User::factory()->role(UserRole::ADMIN)->create();

        $this->actingAs($admin)
            ->post(route('admin.personal.store'), [
                'name' => 'Nuevo Empleado',
                'email' => 'nuevo@lanzastay.test',
                'role' => UserRole::LIMPIEZA,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('admin.personal'));

        $employee = User::query()->where('email', 'nuevo@lanzastay.test')->first();
        $this->assertNotNull($employee);
        $this->assertSame(UserRole::LIMPIEZA, $employee->role);

        $this->actingAs($admin)
            ->put(route('admin.personal.update', $employee), [
                'name' => 'Empleado Actualizado',
                'email' => 'nuevo@lanzastay.test',
                'role' => UserRole::COCINA,
            ])
            ->assertRedirect(route('admin.personal'));

        $employee->refresh();
        $this->assertSame(UserRole::COCINA, $employee->role);

        $this->actingAs($admin)
            ->delete(route('admin.personal.destroy', $employee))
            ->assertRedirect(route('admin.personal'));

        $this->assertNull($employee->fresh());
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->role(UserRole::ADMIN)->create();

        $this->actingAs($admin)
            ->delete(route('admin.personal.destroy', $admin))
            ->assertRedirect();

        $this->assertNotNull($admin->fresh());
    }
}
