<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityReservation;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActivityController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage-reception-operations');

        return Inertia::render('Admin/ActivitiesReservations', [
            'activities' => Activity::query()->latest('date_time')->get(),
            'reservations' => ActivityReservation::query()
                ->with(['activity', 'room'])
                ->latest()
                ->get(),
            'activeTab' => 'reception',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-reception-operations');

        $validated = $this->normalizeCapacity($request->validate($this->rules()));

        Activity::query()->create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        Gate::authorize('manage-reception-operations');

        $activity->update($this->normalizeCapacity($request->validate($this->rules())));

        return redirect()->back();
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        Gate::authorize('manage-reception-operations');

        $activity->delete();

        return redirect()->back();
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:hotel_activity,bus_tour'],
            'date_time' => ['required', 'date'],
            'price' => ['required', 'numeric', 'min:0'],
            'max_seats' => ['nullable', 'integer', 'min:1'],
            'image_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeCapacity(array $validated): array
    {
        if (! array_key_exists('max_seats', $validated) || $validated['max_seats'] === '' || $validated['max_seats'] === null) {
            $validated['max_seats'] = null;
        }

        return $validated;
    }
}
