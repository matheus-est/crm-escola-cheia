<?php

namespace Tests\Feature\Controllers\Tenant\Settings;

use App\Models\Grade;
use App\Models\Role;
use App\Models\School;
use App\Models\Segment;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ModuleSeeder::class);
    $this->seed(PermissionSeeder::class);

    $this->school = School::factory()->create();
    $this->role = Role::firstOrCreate(['name' => 'Gestor']);
    $this->user = User::factory()->create([
        'role_id' => $this->role->id,
        'school_current_id' => $this->school->id,
    ]);
    $this->user->schools()->attach($this->school->id, ['is_active' => true]);

    $this->segment = Segment::factory()->create(['name' => 'Ensino Fundamental I']);
});

it('can list grades page', function () {
    Grade::factory()->create([
        'school_id' => $this->school->id,
        'segment_id' => $this->segment->id,
        'name' => '1º Ano A',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('tenant.grades.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('tenant-settings/Grades')
        ->has('grades.data', 1)
        ->has('segments')
    );
});

it('can create a grade', function () {
    $response = $this->actingAs($this->user)
        ->post(route('tenant.grades.store'), [
            'name' => '2º Ano B',
            'segment_uuid' => $this->segment->uuid,
            'order' => 2,
        ]);

    $response->assertRedirect(route('tenant.grades.index'));

    $this->assertDatabaseHas('grades', [
        'name' => '2º Ano B',
        'segment_id' => $this->segment->id,
        'school_id' => $this->school->id,
    ]);
});

it('validates unique name per school on create', function () {
    Grade::factory()->create([
        'school_id' => $this->school->id,
        'segment_id' => $this->segment->id,
        'name' => '1º Ano A',
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('tenant.grades.store'), [
            'name' => '1º Ano A',
            'segment_uuid' => $this->segment->uuid,
        ]);

    $response->assertSessionHasErrors('name');
});

it('can update a grade', function () {
    $grade = Grade::factory()->create([
        'school_id' => $this->school->id,
        'segment_id' => $this->segment->id,
        'name' => '1º Ano A',
    ]);

    $newSegment = Segment::factory()->create(['name' => 'Ensino Médio']);

    $response = $this->actingAs($this->user)
        ->put(route('tenant.grades.update', ['grade' => $grade->uuid]), [
            'name' => '1º Ano B',
            'segment_uuid' => $newSegment->uuid,
            'order' => 10,
        ]);

    $response->assertRedirect(route('tenant.grades.index'));

    $this->assertDatabaseHas('grades', [
        'id' => $grade->id,
        'name' => '1º Ano B',
        'segment_id' => $newSegment->id,
        'order' => 10,
    ]);
});

it('can delete a grade', function () {
    $grade = Grade::factory()->create([
        'school_id' => $this->school->id,
        'segment_id' => $this->segment->id,
        'name' => 'Turma para Excluir',
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('tenant.grades.destroy', ['grade' => $grade->uuid]));

    $response->assertRedirect(route('tenant.grades.index'));

    $this->assertDatabaseMissing('grades', [
        'id' => $grade->id,
    ]);
});

it('denies access without proper permission', function () {
    $unprivilegedUser = User::factory()->create([
        'role_id' => null,
        'school_current_id' => $this->school->id,
    ]);

    $response = $this->actingAs($unprivilegedUser)
        ->get(route('tenant.grades.index'));

    $response->assertForbidden();
});
