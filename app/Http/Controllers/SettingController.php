<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::query()
            ->orderBy('group')
            ->get()
            ->groupBy('group');

        return view('settings.index', compact('settings'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function store(): RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function show(string $setting): RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function edit(string $setting): RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function destroy(string $setting): RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings'   => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $incoming = $validated['settings'] ?? [];

        // Fetch only IDs that genuinely belong to the current company
        // (CompanyScope is applied automatically by the model)
        $allowedIds = Setting::pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        foreach ($incoming as $rawId => $value) {
            if (! in_array((string) $rawId, $allowedIds, true)) {
                continue; // silently ignore IDs not owned by this company
            }

            Setting::query()
                ->where('id', (int) $rawId)
                ->update(['value' => $value !== null ? strip_tags((string) $value) : null]);
        }

        return back()->with('success', 'Settings updated successfully');
    }
}
