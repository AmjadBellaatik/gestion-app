<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class RepairController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect('/admin/repair-tickets');
    }

    public function create(): RedirectResponse
    {
        return redirect('/admin/repair-tickets/create');
    }

    public function store(): RedirectResponse
    {
        return redirect('/admin/repair-tickets');
    }

    public function show(string $repair): RedirectResponse
    {
        return redirect('/admin/repair-tickets/' . $repair);
    }

    public function edit(string $repair): RedirectResponse
    {
        return redirect('/admin/repair-tickets/' . $repair . '/edit');
    }

    public function update(string $repair): RedirectResponse
    {
        return redirect('/admin/repair-tickets/' . $repair);
    }

    public function destroy(string $repair): RedirectResponse
    {
        return redirect('/admin/repair-tickets');
    }
}
