<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollegeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin.colleges.index', [
            'colleges' => College::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.colleges.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:50'],
            'dean' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        College::query()->create($validated);

        return redirect()
            ->route('admin.colleges.index')
            ->with('status', 'College created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(College $college)
    {
        return view('admin.colleges.show', [
            'college' => $college->load('departments'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(College $college): View
    {
        return view('admin.colleges.edit', [
            'college' => $college,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, College $college): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:50'],
            'dean' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $college->update($validated);

        return redirect()
            ->route('admin.colleges.index')
            ->with('status', 'College updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(College $college): RedirectResponse
    {
        $college->delete();

        return redirect()
            ->route('admin.colleges.index')
            ->with('status', 'College deleted successfully.');
    }
}
