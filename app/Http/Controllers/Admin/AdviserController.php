<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdviserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.advisers.index', [
            'advisers' => User::query()
                ->where('role', UserRole::Adviser)
                ->with('adviserProfile.department')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.advisers.create', [
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAdviser($request);

        DB::transaction(function () use ($validated): void {
            $user = User::query()->create([
                ...$this->userPayload($validated),
                'email_verified_at' => now(),
                'password' => Hash::make($validated['password']),
            ]);

            $user->adviserProfile()->create([
                'department_id' => $validated['department_id'],
                'sex_at_birth' => $validated['sex_at_birth'],
                'rank' => $validated['rank'],
                'home_address' => $validated['home_address'],
            ]);
        });

        return redirect()
            ->route('admin.advisers.index')
            ->with('status', 'Adviser created successfully.');
    }

    public function edit(User $adviser): View
    {
        abort_unless($adviser->isAdviser(), 404);

        return view('admin.users.advisers.edit', [
            'adviser' => $adviser->load('adviserProfile'),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $adviser): RedirectResponse
    {
        abort_unless($adviser->isAdviser(), 404);

        $validated = $this->validateAdviser($request, $adviser);

        DB::transaction(function () use ($adviser, $validated): void {
            $adviser->fill($this->userPayload($validated));
            $adviser->email_verified_at = now();

            if (! empty($validated['password'])) {
                $adviser->password = Hash::make($validated['password']);
            }

            $adviser->save();

            $adviser->adviserProfile()->updateOrCreate([], [
                'department_id' => $validated['department_id'],
                'sex_at_birth' => $validated['sex_at_birth'],
                'rank' => $validated['rank'],
                'home_address' => $validated['home_address'],
            ]);
        });

        return redirect()
            ->route('admin.advisers.index')
            ->with('status', 'Adviser updated successfully.');
    }

    public function destroy(User $adviser): RedirectResponse
    {
        abort_unless($adviser->isAdviser(), 404);

        $adviser->delete();

        return redirect()
            ->route('admin.advisers.index')
            ->with('status', 'Adviser deleted successfully.');
    }

    private function validateAdviser(Request $request, ?User $adviser = null): array
    {
        return $request->validate([
            'id_number' => ['required', 'string', 'max:255', Rule::unique(User::class, 'id_number')->ignore($adviser?->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'name_suffix' => ['nullable', 'string', 'max:255'],
            'sex_at_birth' => ['required', 'string', Rule::in(['Male', 'Female'])],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'rank' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($adviser?->id)],
            'home_address' => ['required', 'string'],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class, 'username')->ignore($adviser?->id)],
            'password' => [$adviser ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    private function userPayload(array $validated): array
    {
        $nameParts = collect([
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
            $validated['name_suffix'] ?? null,
        ])->filter()->implode(' ');

        return [
            'id_number' => $validated['id_number'],
            'name' => $nameParts,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'name_suffix' => $validated['name_suffix'] ?? null,
            'email' => $validated['email'],
            'username' => $validated['username'],
            'role' => UserRole::Adviser,
        ];
    }
}
