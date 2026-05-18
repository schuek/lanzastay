<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::define('manage-users', fn (User $user): bool => UserRole::isAdmin($user->role));

        Gate::define('manage-system-config', fn (User $user): bool => UserRole::isAdmin($user->role));

        Gate::define('manage-reception-operations', fn (User $user): bool => in_array(
            $user->role,
            [UserRole::ADMIN, UserRole::RECEPCION],
            true
        ));

        Gate::define('manage-catalog', fn (User $user): bool => in_array(
            $user->role,
            [UserRole::ADMIN, UserRole::COCINA],
            true
        ));

        Gate::define('view-global-dashboard', fn (User $user): bool => in_array(
            $user->role,
            [UserRole::ADMIN, UserRole::RECEPCION],
            true
        ));

        Gate::define('access-kitchen-orders', fn (User $user): bool => (new \App\Policies\OrderPolicy)->viewKitchen($user));

        Gate::define('access-cleaning-tasks', fn (User $user): bool => (new \App\Policies\OrderPolicy)->viewCleaningTasks($user));

        Gate::define('access-maintenance-tasks', fn (User $user): bool => (new \App\Policies\OrderPolicy)->viewMaintenanceTasks($user));
    }
}
