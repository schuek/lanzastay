<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityReservation;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/ActivitiesReservations', [
            'activities' => Activity::query()->latest('date_time')->get(),
            'reservations' => ActivityReservation::query()
                ->with(['activity', 'room'])
                ->latest()
                ->get(),
            'activeTab' => 'management',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        Activity::query()->create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $activity->update($validated);

        return redirect()->back();
    }

    public function destroy(Activity $activity): RedirectResponse
    {
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
            'max_seats' => ['required', 'integer', 'min:1'],
            'image_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
