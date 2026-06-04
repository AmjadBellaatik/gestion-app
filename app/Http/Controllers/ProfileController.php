<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view(

            'profile.edit',

            [

                'user' => auth()->user()

            ]

        );
    }

    public function update(
        Request $request
    )
    {
        $user = auth()->user();

        $validated = $request->validate([

            'name' => 'required|string|max:255',

            'email' => 'required|email',

            'phone' => 'nullable|string|max:255',

            'address' => 'nullable|string',

            'language' => 'required|in:fr,en,ar',

            'password' => ['nullable', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],

            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',

        ]);

        /*
        |--------------------------------------------------------------------------
        | PROFILE IMAGE
        |--------------------------------------------------------------------------
        */

        if (

            $request->hasFile(
                'profile_picture'
            )

        ) {

            $validated['profile_picture'] =

                $request

                    ->file('profile_picture')

                    ->store(

                        'profile_pictures',

                        'public'

                    );

        }

        /*
        |--------------------------------------------------------------------------
        | PASSWORD
        |--------------------------------------------------------------------------
        */

        if (

            ! empty($validated['password'])

        ) {

            $validated['password'] =

                Hash::make(

                    $validated['password']

                );

        } else {

            unset($validated['password']);

        }

        $user->update($validated);

        return back()->with(

            'success',

            __('messages.updated_successfully')

        );
    }

    public function settings()
    {
        return view(

            'profile.settings',

            [

                'user' => auth()->user()

            ]

        );
    }

    public function updateSettings(
        Request $request
    )
    {
        $user = auth()->user();

        $validated = $request->validate([

            'language' => 'required|in:fr,en,ar',

            'notifications' => 'nullable|boolean',

            'password' => ['nullable', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],

        ]);

        if (

            ! empty($validated['password'])

        ) {

            $validated['password'] =

                Hash::make(

                    $validated['password']

                );

        } else {

            unset($validated['password']);

        }

        $user->update($validated);

        return back()->with(

            'success',

            __('messages.updated_successfully')

        );
    }
}