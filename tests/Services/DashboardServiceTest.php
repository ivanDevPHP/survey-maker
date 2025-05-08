<?php


use App\Http\Resources\SurveyAnswerResourse;
use App\Http\Resources\SurveyResourceDashboard;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Services\DashboardService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;
    protected $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dashboardService = app(DashboardService::class);
    }

    /** @test */
    public function it_gets_dashboard_data()
    {
        // Create a user
        $user = User::factory()->create();

        // Create surveys for the user
        $survey1 = Survey::create([
            'title' => 'Survey 1',
            'user_id' => $user->id,
            'status' => true,
            'description' => 'Description for Survey 1',
            'expire_date' => now()->addDays(10),
            'created_at' => now()->subDays(2),
        ]);

        $survey2 = Survey::create([
            'title' => 'Survey 2',
            'user_id' => $user->id,
            'status' => true,
            'description' => 'Description for Survey 2',
            'expire_date' => now()->addDays(5),
        ]);

        // Create answers for the surveys
        $answer1 = SurveyAnswer::create([
            'survey_id' => $survey1->id,
            'start_date' => now()->subDays(2),
            'end_date' => now()->subDays(1),
        ]);

        $answer2 = SurveyAnswer::create([
            'survey_id' => $survey2->id,
            'start_date' => now()->subDays(1),
            'end_date' => now(),
        ]);

        // Call the method to get dashboard data
        $dashboardData = $this->dashboardService->getDashboardData($user);
        // Assertions
        $this->assertEquals(2, $dashboardData['totalSurveys']); // Total surveys should be 2

        $this->assertInstanceOf(SurveyResourceDashboard::class, $dashboardData['latestSurvey']); // Latest survey should be an instance of SurveyResourceDashboard

        $this->assertEquals(2, $dashboardData['totalAnswers']); // Total answers should be 2

        $this->assertCount(2, $dashboardData['latestAnswers']); // Latest answers should contain 2 items
        $this->assertInstanceOf(SurveyAnswerResourse::class, $dashboardData['latestAnswers']->first()); // Each item in latest answers should be an instance of SurveyAnswerResourse
    }

    /** @test */
    public function it_returns_paginated_logs_for_a_user()
    {
        // Arrange: create user and related survey
        $user = User::factory()->create();
        $survey = Survey::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create 6 survey answers (will test pagination limit of 5)
        SurveyAnswer::factory()->count(6)->create([
            'survey_id' => $survey->id,
        ]);

        // Act: call the method under test
        $paginator = $this->getLogs($user);

        // Assert: correct paginator returned
        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);

        // Assert: page contains only 5 items
        $this->assertCount(5, $paginator->items());

        // Assert: pagination meta data is correct
        $this->assertEquals(1, $paginator->currentPage());
        $this->assertEquals(5, $paginator->perPage());
        $this->assertEquals(6, $paginator->total());

        // Assert: survey title is included in result
        $this->assertEquals($survey->title, $paginator->first()->title);
    }

    /**
     * Method under test: get paginated logs for a user
     */
    protected function getLogs(User $user): LengthAwarePaginator
    {
        return SurveyAnswer::query()
            ->select('survey_answers.*', 'surveys.title as title')
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('surveys.user_id', $user->id)
            ->orderByDesc('survey_answers.end_date')
            ->paginate(5);
    }

}
