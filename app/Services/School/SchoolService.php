<?php

declare(strict_types=1);

namespace App\Services\School;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SchoolService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = School::query();

        if (array_key_exists('razao_social', $filters) && $filters['razao_social'] !== null && $filters['razao_social'] !== '') {
            $query->where('razao_social', 'LIKE', '%'.$filters['razao_social'].'%');
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(15);
    }

    public function create(array $data): School
    {
        $units = $data['units'] ?? [];
        unset($data['units']);

        $school = School::query()->create($data);

        foreach ($units as $unit) {
            $school->units()->create($unit);
        }

        return $school;
    }

    public function update(School $school, array $data): School
    {
        $units = null;

        if (array_key_exists('units', $data)) {
            $units = $data['units'];
            unset($data['units']);
        }

        $school->update($data);

        if ($units !== null) {
            $school->units()->delete();

            foreach ($units as $unit) {
                $school->units()->create($unit);
            }
        }

        return $school->fresh();
    }

    public function destroy(School $school): void
    {
        $school->delete();
    }

    public function lookupCnpj(string $cnpj): array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        $response = Http::get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");

        if ($response->status() !== 200) {
            throw new \RuntimeException('CNPJ não encontrado ou API indisponível');
        }

        return $response->json();
    }

    public function attachUser(School $school, User $user, Role $role): void
    {
        if (! in_array($role->name, ['Gestor', 'Comercial'], strict: true)) {
            throw ValidationException::withMessages([
                'role' => 'Role inválida para vínculo de escola',
            ]);
        }

        if ($school->users()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'user' => 'Usuário já vinculado a esta escola',
            ]);
        }

        $school->users()->attach($user->id, ['is_active' => true]);
    }

    public function detachUser(School $school, User $user): void
    {
        $school->users()->detach($user->id);
    }
}
