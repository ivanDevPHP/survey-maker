<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyAnswerRequest;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Services\SurveyService;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class SurveyController extends Controller
{
    protected $surveyService;

    public function __construct(SurveyService $surveyService)
    {
        $this->surveyService = $surveyService;
    }

    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return $this->surveyService->index($request);
    }

    /**
     * @param StoreSurveyRequest $request
     * @return SurveyResource
     * @throws ValidationException
     */
    public function store(StoreSurveyRequest $request): SurveyResource
    {
        $survey = $this->surveyService->store($request);
        return new SurveyResource($survey);
    }

    /**
     * @param StoreSurveyAnswerRequest $request
     * @param Survey $survey
     * @return Response
     */
    public function storeAnswer(StoreSurveyAnswerRequest $request, Survey $survey): Response
    {
        return $this->surveyService->storeAnswer($survey, $request);
    }

    /**
     * @param Survey $survey
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Model[]
     */
    public function getAnswers(Survey $survey): array
    {
        return $this->surveyService->getAnswersById($survey);
    }


    /**
     * @param UpdateSurveyRequest $request
     * @param Survey $survey
     * @return SurveyResource
     * @throws ValidationException
     */
    public function update(UpdateSurveyRequest $request, Survey $survey): SurveyResource
    {
        $updatedSurvey = $this->surveyService->update($survey, $request);
        return new SurveyResource($updatedSurvey);
    }

    /**
     * @param Survey $survey
     * @param Request $request
     * @return Response
     */
    public function destroy(Survey $survey, Request $request): Response
    {
        return $this->surveyService->destroy($survey);
    }

    /**
     * @param Survey $survey
     * @param Request $request
     * @return SurveyResource
     */
    public function show(Survey $survey, Request $request): SurveyResource
    {
        $user = $request->user();
        if ($user->id !== $survey->user_id) {
            return abort(403, 'Unauthorized action.');
        }

        return new SurveyResource($survey);
    }

    /**
     * @param Survey $survey
     * @return SurveyResource
     */
    public function showForGuest(Survey $survey): SurveyResource
    {
        return new SurveyResource($survey);
    }
}
