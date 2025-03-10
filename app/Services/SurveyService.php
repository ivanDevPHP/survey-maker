<?php

namespace App\Services;

use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionAnswer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Requests\StoreSurveyAnswerRequest;
use Illuminate\Validation\ValidationException;
use JsonException;

class SurveyService
{
    /**
     * @param StoreSurveyRequest $request
     * @return Survey
     * @throws ValidationException
     */
    public function store(StoreSurveyRequest $request): Survey
    {
        $data = $request->validated();

        if (isset($data['image'])) {
            $data['image'] = $this->saveImage($data['image']);
        } else {
            $data['image'] = 'images/default-survey.png';
        }

        $survey = Survey::create($data);

        foreach ($data['questions'] as $question) {
            $question['survey_id'] = $survey->id;
            $this->createQuestion($question);
        }

        return $survey;
    }

    /**
     * @param Survey $survey
     * @param StoreSurveyAnswerRequest $request
     * @return Response
     */
    public function storeAnswer(Survey $survey, StoreSurveyAnswerRequest $request): Response
    {
        $validated = $request->validated();

        $surveyAnswer = SurveyAnswer::create([
            'survey_id' => $survey->id,
            'start_date' => now(),
            'end_date' => now(),
        ]);

        foreach ($validated['answers'] as $questionId => $answer) {
            $question = SurveyQuestion::where(['id' => $questionId, 'survey_id' => $survey->id])->first();

            if (!$question) {
                return response("Invalid question ID: \"$questionId\"", 400);
            }

            $data = [
                'survey_question_id' => $questionId,
                'survey_answer_id' => $surveyAnswer->id,
                'answer' => is_array($answer) ? json_encode($answer) : $answer
            ];

            SurveyQuestionAnswer::create($data);
        }

        return response("", 201);
    }

    /**
     * @param Survey $survey
     * @param UpdateSurveyRequest $request
     * @return Survey
     * @throws ValidationException
     */
    public function update(Survey $survey, UpdateSurveyRequest $request): Survey
    {
        $data = $request->validated();

        if (isset($data['image'])) {
            $data['image'] = $this->saveImage($data['image']);
            if ($survey->image) {
                File::delete(public_path($survey->image));
            }
        } else {
            $data['image'] = 'images/default-survey.png';
        }

        $survey->update($data);

        $existingIds = $survey->questions()->pluck('id')->toArray();
        $newIds = Arr::pluck($data['questions'], 'id');

        $toDelete = array_diff($existingIds, $newIds);
        $toAdd = array_diff($newIds, $existingIds);

        SurveyQuestion::destroy($toDelete);

        foreach ($data['questions'] as $question) {
            if (in_array($question['id'], $toAdd)) {
                $question['survey_id'] = $survey->id;
                $this->createQuestion($question);
            }
        }

        $questionMap = collect($data['questions'])->keyBy('id');
        foreach ($survey->questions as $question) {
            if (isset($questionMap[$question->id])) {
                $this->updateQuestion($question, $questionMap[$question->id]);
            }
        }

        return $survey;
    }

    /**
     * @param Survey $survey
     * @return Response
     */
    public function destroy(Survey $survey): Response
    {
        $survey->delete();

        if ($survey->image) {
            File::delete(public_path($survey->image));
        }

        return response('', 204);
    }

    /**
     * @param $image
     * @return string
     * @throws Exception
     */
    private function saveImage($image): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $image = substr($image, strpos($image, ',') + 1);
            $type = strtolower($type[1]);

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new Exception('Invalid image type.');
            }

            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);

            if ($image === false) {
                throw new Exception('Failed to base64 decode the image.');
            }
        } else {
            throw new Exception('Invalid image format.');
        }

        $dir = 'images/';
        $file = Str::random() . '.' . $type;
        $absolutePath = public_path($dir);
        $relativePath = $dir . $file;

        if (!File::exists($absolutePath)) {
            File::makeDirectory($absolutePath, 0755, true);
        }

        file_put_contents($relativePath, $image);

        return $relativePath;
    }

    /**
     * Create a new survey question.
     *
     * @param array $data
     * @return SurveyQuestion
     * @throws ValidationException
     * @throws JsonException
     */
    private function createQuestion(array $data): SurveyQuestion
    {
        $data = $this->prepareQuestionData($data);

        $validator = Validator::make($data, $this->getQuestionValidationRules());

        return SurveyQuestion::create($validator->validated());
    }

    /**
     * Update an existing survey question.
     *
     * @param SurveyQuestion $question
     * @param array $data
     * @return bool
     * @throws ValidationException
     * @throws JsonException
     */
    private function updateQuestion(SurveyQuestion $question, array $data): bool
    {
        $data = $this->prepareQuestionData($data);

        $validator = Validator::make($data, $this->getQuestionValidationRules());

        return $question->update($validator->validated());
    }

    /**
     * Prepare survey question data before validation.
     *
     * @param array $data
     * @return array|string
     * @throws JsonException
     */
    private function prepareQuestionData(array $data): array|string
    {
        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = json_encode($data['data'], JSON_THROW_ON_ERROR);
        }
        return $data;
    }

    /**
     * Get validation rules for survey questions.
     *
     * @return array
     */
    private function getQuestionValidationRules(): array
    {
        return [
            'question' => 'required|string',
            'type' => ['required', Rule::in([
                Survey::TYPE_RADIO,
                Survey::TYPE_CHECKBOX,
                Survey::TYPE_SELECT,
                Survey::TYPE_TEXT,
                Survey::TYPE_TEXTAREA
            ])],
            'description' => 'nullable|string',
            'data' => 'present',
            'survey_id' => 'exists:App\Models\Survey,id'
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        return SurveyResource::collection(Survey::where('user_id', $user->id)->paginate(5));
    }
}
