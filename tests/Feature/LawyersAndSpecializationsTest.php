<?php

use App\Models\User;
use App\Models\Lawyer;
use App\Models\ProblemType;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\LawyerPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run all permission seeders to initialize permissions and roles
    $this->seed(PermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);
    $this->seed(LawyerPermissionSeeder::class);

    // Create a Super Admin user
    $this->adminUser = User::factory()->create([
        'status' => true,
    ]);
    $this->adminUser->assignRole('Super Admin');
});

test('unauthenticated users are redirected from admin layers', function () {
    $response = $this->get(route('admin.lawyers.index'));
    $response->assertRedirect(route('admin.auth.login'));
});

test('admin can view lawyers index page', function () {
    $response = $this->actingAs($this->adminUser)->get(route('admin.lawyers.index'));
    $response->assertStatus(200);
    $response->assertViewIs('admin.lawyers.index');
});

test('admin can view specializations index page', function () {
    $response = $this->actingAs($this->adminUser)->get(route('admin.specializations.index'));
    $response->assertStatus(200);
    $response->assertViewIs('admin.specializations.index');
});

test('admin can fetch lawyers datatable via ajax', function () {
    $response = $this->actingAs($this->adminUser)->getJson(route('admin.lawyers.index'), [
        'HTTP_X-Requested-With' => 'XMLHttpRequest'
    ]);
    $response->assertStatus(200);
    $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
});

test('admin can fetch specializations datatable via ajax', function () {
    $response = $this->actingAs($this->adminUser)->getJson(route('admin.specializations.index'), [
        'HTTP_X-Requested-With' => 'XMLHttpRequest'
    ]);
    $response->assertStatus(200);
    $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
});

test('admin can create a specialization', function () {
    $response = $this->actingAs($this->adminUser)->postJson(route('admin.specializations.store'), [
        'slug' => 'test-intellectual-property',
        'title' => 'Intellectual Property Law',
        'is_default' => '1',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('problem_types', [
        'slug' => 'test-intellectual-property',
        'title' => 'Intellectual Property Law',
        'is_default' => 1,
    ]);
});

test('admin can create a lawyer with specializations', function () {
    // First create a specialization
    $spec = ProblemType::create([
        'slug' => 'criminal-defense',
        'title' => 'Criminal Defense',
        'is_default' => false,
    ]);

    $response = $this->actingAs($this->adminUser)->postJson(route('admin.lawyers.store'), [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'phone' => '1234567890',
        'is_active' => '1',
        'specializations' => [$spec->id],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('lawyers', [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'phone' => '1234567890',
        'is_active' => 1,
    ]);

    $lawyer = Lawyer::where('email', 'johndoe@example.com')->first();
    expect($lawyer->specializations)->toHaveCount(1);
    expect($lawyer->specializations->first()->id)->toBe($spec->id);
});

test('admin can update a lawyer details and specializations', function () {
    $spec1 = ProblemType::create(['slug' => 'tax-law', 'title' => 'Tax Law']);
    $spec2 = ProblemType::create(['slug' => 'family-law', 'title' => 'Family Law']);

    $lawyer = Lawyer::create([
        'name' => 'Jane Smith',
        'email' => 'janesmith@example.com',
        'phone' => '9876543210',
        'is_active' => true,
    ]);
    $lawyer->specializations()->attach($spec1->id);

    $response = $this->actingAs($this->adminUser)->putJson(route('admin.lawyers.update', $lawyer), [
        'name' => 'Jane R. Smith',
        'email' => 'janersmith@example.com',
        'phone' => '9876543211',
        'is_active' => '1',
        'specializations' => [$spec2->id],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('lawyers', [
        'id' => $lawyer->id,
        'name' => 'Jane R. Smith',
        'email' => 'janersmith@example.com',
        'phone' => '9876543211',
    ]);

    $lawyer->refresh();
    expect($lawyer->specializations)->toHaveCount(1);
    expect($lawyer->specializations->first()->id)->toBe($spec2->id);
});

test('admin can delete a lawyer', function () {
    $lawyer = Lawyer::create([
        'name' => 'ToDelete Lawyer',
        'email' => 'todelete@example.com',
        'phone' => '0000000000',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->adminUser)->deleteJson(route('admin.lawyers.destroy', $lawyer));

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseMissing('lawyers', ['id' => $lawyer->id]);
});
