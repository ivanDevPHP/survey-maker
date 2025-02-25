<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Http\Resources\SurveyResourceDashboard;
use App\Http\Resources\SurveyAnswerResourse;

class DashboardService
{
    public function getDashboardData($user)
    {
        // Total Number of Surveys
        $total = Survey::query()->where('user_id', $user->id)->count();

        // Latest Survey
        $latest = Survey::query()->where('user_id', $user->id)->latest('created_at')->first();

        // Total Number of Answers
        $totalAnswers = SurveyAnswer::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('surveys.user_id', $user->id)
            ->count();

        // Latest 5 Answers
        $latestAnswers = SurveyAnswer::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('surveys.user_id', $user->id)
            ->orderBy('end_date', 'DESC')
            ->limit(5)
            ->getModels('survey_answers.*');

        return [
            'totalSurveys' => $total,
            'latestSurvey' => $latest ? new SurveyResourceDashboard($latest) : null,
            'totalAnswers' => $totalAnswers,
            'latestAnswers' => SurveyAnswerResourse::collection($latestAnswers)
        ];
    }
}
