<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::query()

            ->orderBy('group')

            ->get()

            ->groupBy('group');

        return view(

            'settings.index',

            compact('settings')

        );
    }

    public function create(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function store(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function show(string $setting): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function edit(string $setting): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function destroy(string $setting): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('settings.index');
    }

    public function update(
        Request $request
    )
    {
        $settings =
            $request->input(
                'settings',
                []
            );

        foreach (

            $settings as $id => $value

        ) {

            Setting::query()

                ->where('id', $id)

                ->update([

                    'value' => $value,

                ]);
        }

        return back()->with(

            'success',

            'Settings updated successfully'

        );
    }
}