<?php

namespace Tests\Services;

use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class SurveyServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_stores_survey()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $data = [
            'title' => 'PHPUnit',
            'image' => null,
            'user_id' => $user->id,
            'status' => true,
            'description' => 'Is a programmer-oriented testing framework for PHP',
            'expire_date' => null,
            'questions' => [
                [
                    'id' => 1,
                    'type' => 'text',
                    'question' => 'Question 1',
                    'description' => '123',
                    'data' => [],
                ],
            ],
        ];

        //Auth the user
        $this->actingAs($user);

        $response = $this->postJson('/api/survey', $data);

        $this->assertDatabaseHas('surveys', [
            'title' => 'PHPUnit',
            'user_id' => $user->id,
        ]);

        $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'image_url',
                'title',
                'slug',
                'status',
                'description',
                'created_at',
                'updated_at',
                'expire_at',
                'questions' => [
                    '*' => [
                        'id',
                        'type',
                        'question',
                        'description',
                        'data',
                    ],
                ],
            ],
        ]);
    }
}
