<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class SaleController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect('/admin/sales');
    }

    public function create(): RedirectResponse
    {
        return redirect('/admin/sales/create');
    }

    public function store(): RedirectResponse
    {
        return redirect('/admin/sales');
    }

    public function show(string $sale): RedirectResponse
    {
        return redirect('/admin/sales/' . $sale);
    }

    public function edit(string $sale): RedirectResponse
    {
        return redirect('/admin/sales/' . $sale . '/edit');
    }

    public function update(string $sale): RedirectResponse
    {
        return redirect('/admin/sales/' . $sale);
    }

    public function destroy(): RedirectResponse
    {
        return redirect('/admin/sales');
    }
}
