<?php

namespace Tests\Feature;

use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolution_center_link_appears_for_signed_in_users(): void
    {
        $user = User::create([
            'unique_id' => 'test-nav', 'first_name' => 'Nav', 'last_name' => 'User',
            'email' => 'nav@test.local', 'status' => 'Active',
        ]);

        $this->actingAs($user)
            ->get(route('admin.wait-list.index'))
            ->assertOk()
            ->assertSee('Resolution Center')
            ->assertSee(route('admin.resolution-center.operations'));
    }

    public function test_guests_are_redirected_and_never_see_the_sidebar(): void
    {
        $this->get(route('admin.wait-list.index'))
            ->assertRedirect();
    }
}
