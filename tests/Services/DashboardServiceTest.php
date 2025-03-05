<?php


use App\Http\Resources\SurveyAnswerResourse;
use App\Http\Resources\SurveyResourceDashboard;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Services\DashboardService;
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
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('Password123!'),
        ]);

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
}
