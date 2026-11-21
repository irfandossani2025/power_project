<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmsVehicleTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_employee_compliance_dates(): void
    {
        $company = Company::create(['name' => 'Power Trading']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('employees.store'), ['employee_number' => 'EMP-001', 'full_name' => 'Ali Al Balushi', 'is_omani' => '1', 'omani_id_number' => '12345678', 'omani_id_expiry_date' => now()->addDays(25)->format('Y-m-d'), 'is_active' => '1'])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', ['company_id' => $company->id, 'employee_number' => 'EMP-001', 'is_omani' => 1]);
    }

    public function test_inventory_user_can_track_vehicle_but_not_employee_records(): void
    {
        $company = Company::create(['name' => 'Power Trading']);
        $inventory = User::factory()->create(['role' => 'inventory']);

        $this->actingAs($inventory)->post(route('vehicles.store'), ['vehicle_number' => 'VH-001', 'registration_plate' => 'MUS 12345', 'mulkiya_expiry_date' => now()->addDays(20)->format('Y-m-d'), 'is_active' => '1'])->assertRedirect(route('vehicles.index'));
        $this->actingAs($inventory)->get(route('employees.index'))->assertForbidden();
        $this->assertDatabaseHas('vehicles', ['company_id' => $company->id, 'vehicle_number' => 'VH-001']);
    }

    public function test_dashboard_shows_employee_and_vehicle_expiry_alerts(): void
    {
        $company = Company::create(['name' => 'Power Trading']);
        $admin = User::factory()->create(['role' => 'admin']);
        Employee::create(['company_id' => $company->id, 'employee_number' => 'EMP-001', 'full_name' => 'Ali Al Balushi', 'is_omani' => true, 'omani_id_expiry_date' => now()->addDays(10), 'is_active' => true]);
        Vehicle::create(['company_id' => $company->id, 'vehicle_number' => 'VH-001', 'registration_plate' => 'MUS 12345', 'mulkiya_expiry_date' => now()->addDays(15), 'is_active' => true]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Compliance alerts')->assertSee('Omani ID card')->assertSee('Mulkiya');
    }
}
