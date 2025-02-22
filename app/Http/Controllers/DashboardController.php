<?php

namespace App\Http\Controllers;

use App\Http\Resources\SurveyAnswerResourse;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Models\SurveyAnswers;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request){
        $user = $request->user();

        //Total Number of Surveys
        $total = Survey::query()->where('user_id', $user->id)->count();

        //Latest Survey
        $latest = Survey::query()->where('user_id', $user->id)->latest('created_at')->first();

        //Total Number of answers
        $totalAnswers = SurveyAnswers::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('surveys.user_id', $user->id)
            ->count();

        //Latest 5 answers
        $latestAnswers = SurveyAnswers::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('surveys.user_id', $user->id)
            ->orderBy('end_date', 'DESC')
            ->limit(5)
            ->getModels('survey_answers.*');

        return [
            'totalSurveys' => $total,
            'latestSurvey' => $latest ? new SurveyResource($latest) : null,
            'totalAnswers' => $totalAnswers,
            'latestAnswers' => SurveyAnswerResourse::collection($latestAnswers)
        ];
    }
}
