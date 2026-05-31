<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect('/admin/users');
    }

    public function create(): RedirectResponse
    {
        return redirect('/admin/users/create');
    }

    public function store(): RedirectResponse
    {
        return redirect('/admin/users');
    }

    public function show(string $user): RedirectResponse
    {
        return redirect('/admin/users/' . $user);
    }

    public function edit(string $user): RedirectResponse
    {
        return redirect('/admin/users/' . $user . '/edit');
    }

    public function update(string $user): RedirectResponse
    {
        return redirect('/admin/users/' . $user);
    }

    public function destroy(string $user): RedirectResponse
    {
        return redirect('/admin/users');
    }
}
