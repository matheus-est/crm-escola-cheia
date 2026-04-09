<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Student\StudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

function studentMasterUser(): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Master'],
        ['description' => 'Master', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function studentGestorUser(School $school): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Gestor'],
        ['description' => 'Gestor', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    return $user;
}

function makeSchoolForStudentTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 5000), 14, '0', STR_PAD_LEFT),
        'legal_name' => 'Escola Student '.$counter,
    ]);
}

function makeStudent(School $school, string $cpf = '123.456.789-00'): Student
{
    return Student::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => 'Aluno Teste',
        'cpf' => $cpf,
    ]);
}

it('GET index retorna 200 para usuário Master autenticado', function (): void {
    $user = studentMasterUser();
    $school = makeSchoolForStudentTests();

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.students.index'))
        ->assertStatus(200);
});

it('GET index retorna 302 para usuário não autenticado', function (): void {
    $school = makeSchoolForStudentTests();

    $this->get(route('tenant.students.index'))
        ->assertStatus(302);
});

it('POST store cria um Student e redireciona', function (): void {
    $user = studentMasterUser();
    $school = makeSchoolForStudentTests();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.students.store'), [
            'name' => 'João da Silva',
            'cpf' => '123.456.789-00',
            'data_nascimento' => '2010-05-15',
        ])
        ->assertRedirect(route('tenant.students.index'));

    $this->assertDatabaseHas('students', [
        'school_id' => $school->id,
        'name' => 'João da Silva',
        'cpf' => '123.456.789-00',
    ]);
});

it('CPF duplicado no mesmo tenant retorna 422', function (): void {
    $user = studentMasterUser();
    $school = makeSchoolForStudentTests();
    makeStudent($school, '123.456.789-00');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.students.store'), [
            'name' => 'Outro Aluno',
            'cpf' => '123.456.789-00',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['cpf']);
});

it('CPF igual em tenant diferente é aceito', function (): void {
    $user = studentMasterUser();
    $school1 = makeSchoolForStudentTests();
    $school2 = makeSchoolForStudentTests();
    makeStudent($school1, '123.456.789-00');

    app()->instance('tenant.school_id', $school2->id);

    $this->actingAs($user)
        ->post(route('tenant.students.store'), [
            'name' => 'Aluno Outro Tenant',
            'cpf' => '123.456.789-00',
        ])
        ->assertRedirect(route('tenant.students.index'));

    $this->assertDatabaseHas('students', [
        'school_id' => $school2->id,
        'name' => 'Aluno Outro Tenant',
        'cpf' => '123.456.789-00',
    ]);
});

it('GET lookup retorna 200 com Student existente', function (): void {
    $user = studentMasterUser();
    $school = makeSchoolForStudentTests();
    makeStudent($school, '123.456.789-00');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->getJson(route('tenant.students.lookup', ['123.456.789-00']))
        ->assertStatus(200)
        ->assertJsonFragment(['cpf' => '123.456.789-00']);
});

it('GET lookup retorna 404 para CPF inexistente', function (): void {
    $user = studentMasterUser();
    $school = makeSchoolForStudentTests();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->getJson(route('tenant.students.lookup', ['999.999.999-99']))
        ->assertStatus(404);
});

it('PUT update atualiza o Student e redireciona', function (): void {
    $user = studentMasterUser();
    $school = makeSchoolForStudentTests();
    $student = makeStudent($school, '123.456.789-00');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->put(route('tenant.students.update', [$student]), [
            'name' => 'João Atualizado',
            'cpf' => '123.456.789-00',
        ])
        ->assertRedirect(route('tenant.students.index'));

    $this->assertDatabaseHas('students', [
        'id' => $student->id,
        'name' => 'João Atualizado',
    ]);
});

it('DELETE destroy remove o Student e redireciona', function (): void {
    $user = studentMasterUser();
    $school = makeSchoolForStudentTests();
    $student = makeStudent($school, '123.456.789-00');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->delete(route('tenant.students.destroy', [$student]))
        ->assertRedirect(route('tenant.students.index'));

    $this->assertDatabaseMissing('students', [
        'id' => $student->id,
    ]);
});

it('findOrCreate retorna existente se CPF já cadastrado', function (): void {
    $school = makeSchoolForStudentTests();
    $existing = makeStudent($school, '123.456.789-00');

    app()->instance('tenant.school_id', $school->id);

    $service = app(StudentService::class);
    $result = $service->findOrCreate([
        'name' => 'Outro Nome',
        'cpf' => '123.456.789-00',
    ]);

    expect($result->id)->toBe($existing->id);
    expect(Student::withoutTenantScope()->where('school_id', $school->id)->count())->toBe(1);
});
