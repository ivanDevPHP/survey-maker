<?php

namespace Tests\Services;

use App\Models\Survey;
use App\Models\SurveyQuestion;
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
            'image' => 'images/default-survey.png'
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

    /** @test */
    public function it_stores_survey_answer()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $survey = Survey::create([
            'title' => 'PHPUnit',
            'user_id' => $user->id,
            'status' => true,
            'description' => 'Is a programmer-oriented testing framework for PHP',
            'expire_date' => null,
        ]);

        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'type' => 'text',
            'question' => 'Question 1',
            'description' => '123',
            'data' => '[]',
        ]);

        $data = [
            'answers' => [
                $question->id => "Test 123",
            ]
        ];

        // Auth the user
        $this->actingAs($user);


        $this->postJson("api/survey/{$survey->id}/answer", $data);

        $this->assertDatabaseHas('survey_answers', [
            'survey_id' => $survey->id,
        ]);

        $this->assertDatabaseHas('survey_question_answers', [
            'survey_question_id' => $survey->id,
            'answer' => "Test 123"
        ]);


    }

    /** @test */
    public function it_updates_survey()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $survey = Survey::create([
            'title' => 'PHPUnit',
            'user_id' => $user->id,
            'status' => true,
            'description' => 'Is a programmer-oriented testing framework for PHP',
            'expire_date' => null,
        ]);

        $data = [
            'id' => $survey->id,
            'image_url' => null,
            'title' => 'Updated PHPUnit',
            'slug' => 'PHPUnit',
            'status' => true,
            'description' => 'Updated description',
            'expire_at' => null,
            'questions' => [
                [
                    'id' => null,
                    'type' => 'text',
                    'question' => 'test php 123',
                    'description' => null,
                    'data' => [],
                ],
            ],
        ];

        // Auth the user
        $this->actingAs($user);

        $response = $this->putJson("/api/survey/{$survey->id}", $data);

        $this->assertDatabaseHas('surveys', [
            'id' => $survey->id,
            'title' => 'Updated PHPUnit',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
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
                    'questions',
                ],
            ]);
    }

    /** @test */
    public function it_deletes_survey()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $survey = Survey::create([
            'title' => 'PHPUnit',
            'user_id' => $user->id,
            'status' => true,
            'description' => 'Is a programmer-oriented testing framework for PHP',
            'expire_date' => null,
        ]);

        // Auth the user
        $this->actingAs($user);

        $response = $this->deleteJson("/api/survey/{$survey->id}");

        $this->assertDatabaseMissing('surveys', [
            'id' => $survey->id,
        ]);

        $response->assertStatus(204);
    }

    /** @test */
    public function it_shows_survey()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $survey = Survey::create([
            'title' => 'PHPUnit',
            'user_id' => $user->id,
            'status' => true,
            'description' => 'Is a programmer-oriented testing framework for PHP',
            'expire_date' => null,
        ]);

        // Auth the user
        $this->actingAs($user);

        $response = $this->getJson("/api/survey/{$survey->id}");

        $response->assertStatus(200)
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
                    'questions',
                ],
            ]);
    }

    /** @test */
    public function it_shows_survey_for_guest()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $survey = Survey::create([
            'title' => 'PHPUnit',
            'user_id' => $user->id,
            'status' => true,
            'description' => 'Is a programmer-oriented testing framework for PHP',
            'expire_date' => null,
        ]);

        $response = $this->getJson("/api/survey-by-slug/{$survey->slug}");

        $response->assertStatus(200)
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
                    'questions',
                ],
            ]);
    }
}
