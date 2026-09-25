<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_tokens(): void
    {
        $this->get('/tokens')->assertRedirect('/login');
        $this->post('/tokens', ['name' => 'x'])->assertRedirect('/login');
    }

    public function test_new_token_is_shown_once_and_works_for_mcp(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tokens', ['name' => 'MacBook'])->assertRedirect('/tokens');
        $plain = session('new_token');
        $this->assertNotEmpty($plain);

        $this->get('/tokens')->assertInertia(fn (Assert $page) => $page
            ->component('tokens')
            ->where('new_token', $plain)
            ->where('mcp_url', url('/mcp'))
            ->has('tokens', 1)
            ->where('tokens.0.name', 'MacBook')
            ->missing('tokens.0.token'));

        $this->get('/tokens')->assertInertia(fn (Assert $page) => $page->where('new_token', null));

        auth()->logout();
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], ['Authorization' => "Bearer {$plain}"])->assertOk();
    }

    public function test_revoke_only_own_token(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = $user->createToken('mine')->accessToken;
        $theirs = $other->createToken('theirs')->accessToken;

        $this->actingAs($user)->delete("/tokens/{$theirs->id}")->assertRedirect();
        $this->assertModelExists($theirs);

        $this->delete("/tokens/{$mine->id}")->assertRedirect();
        $this->assertModelMissing($mine);
    }

    public function test_token_name_is_required(): void
    {
        $this->actingAs(User::factory()->create())->post('/tokens', ['name' => ''])->assertSessionHasErrors('name');
    }
}
