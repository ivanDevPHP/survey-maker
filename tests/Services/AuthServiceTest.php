<?php

namespace Tests\Services;

use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_registers_a_user_and_returns_user_and_token()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/register', $userData);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    /** @test */
    public function it_fails_to_register_a_user_with_invalid_password()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password',
            'password_confirmation' => 'Password',
        ];

        $response = $this->postJson('/api/register', $userData);


        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function it_logs_in_a_user_with_valid_credentials()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $credentials = [
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ];

        $response = $this->postJson('/api/login', $credentials);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ]);

        $this->assertTrue(Auth::check());
    }

    /** @test */
    public function it_fails_to_log_in_with_invalid_credentials()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $credentials = [
            'email' => 'john@example.com',
            'password' => 'WrongPassword',
        ];

        $response = $this->postJson('/api/login', $credentials);

        $response->assertStatus(422)
            ->assertJson(['error' => 'The provided credentials are incorrect.']);

        $this->assertFalse(Auth::check());
    }

    /** @test */
    public function it_logs_out_a_user_and_deletes_token()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $token = $user->createToken('main')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNull($user->fresh()->tokens()->first());
    }
}
