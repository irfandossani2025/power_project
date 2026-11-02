<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_deactivate_a_team_member(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->post(route('users.store'), ['name'=>'Sales User','username'=>'sales_user','email'=>'sales@example.com','role'=>'sales','password'=>'password123','password_confirmation'=>'password123'])->assertSessionHasNoErrors();
        $member=User::where('username','sales_user')->firstOrFail();
        $this->assertDatabaseHas('users',['id'=>$member->id,'role'=>'sales','is_active'=>1]);
        $this->actingAs($admin)->patch(route('users.update',$member), ['role'=>'sales','is_active'=>0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users',['id'=>$member->id,'is_active'=>0]);
    }

    public function test_non_admin_cannot_open_team_access(): void
    {
        $user=User::factory()->create(['role'=>'sales']);
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    }
}
