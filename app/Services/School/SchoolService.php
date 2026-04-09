<?php

declare(strict_types=1);

namespace App\Services\School;

use App\Mail\WelcomeSchoolUserMail;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SchoolService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = School::query();

        if (array_key_exists('legal_name', $filters) && $filters['legal_name'] !== null && $filters['legal_name'] !== '') {
            $query->where('legal_name', 'LIKE', '%'.$filters['legal_name'].'%');
        }

        if (array_key_exists('cnpj', $filters) && $filters['cnpj'] !== null && $filters['cnpj'] !== '') {
            $query->where('cnpj', 'LIKE', '%'.$filters['cnpj'].'%');
        }

        if (array_key_exists('unit', $filters) && $filters['unit'] !== null && $filters['unit'] !== '') {
            $query->whereHas('units', function ($q) use ($filters): void {
                $q->where('name', 'LIKE', '%'.$filters['unit'].'%');
            });
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('sort_by', $filters) && $filters['sort_by'] !== null && $filters['sort_by'] !== '') {
            $sortBy = $filters['sort_by'];
            $sortDir = array_key_exists('sort_dir', $filters) && $filters['sort_dir'] !== null
                ? $filters['sort_dir']
                : 'asc';

            $allowedSortColumns = ['legal_name', 'cnpj', 'slug', 'status'];
            if (in_array($sortBy, $allowedSortColumns, strict: true)) {
                $query->orderBy($sortBy, $sortDir);
            }
        } else {
            $query->orderBy('legal_name', 'asc');
        }

        $perPage = array_key_exists('per_page', $filters) && $filters['per_page'] !== null
            ? (int) $filters['per_page']
            : 15;

        if ($perPage <= 0) {
            $perPage = 15;
        }

        return $query->paginate($perPage);
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

        $data = $response->json();

        return [
            'legal_name' => $data['razao_social'] ?? '',
            'trade_name' => $data['nome_fantasia'] ?? '',
            'zip_code' => $data['cep'] ?? '',
            'street' => $data['logradouro'] ?? '',
            'number' => $data['numero'] ?? '',
            'complement' => $data['complemento'] ?? '',
            'neighborhood' => $data['bairro'] ?? '',
            'city' => $data['municipio'] ?? '',
            'state' => $data['uf'] ?? '',
        ];
    }

    public function createUserForSchool(array $data, School $school, Role $role): User
    {
        $temporaryPassword = Str::password(16);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'role_id' => $role->id,
        ]);

        Mail::to($user)->queue(new WelcomeSchoolUserMail($user, $temporaryPassword, $school));

        $this->attachUser($school, $user, $role);

        return $user;
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
