<?php

namespace App\Http\Controllers\Backend;

use Exception;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveQuestionnaireRequest;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Models\Questionnaire;
use App\Models\Question;
use App\Models\QuestionFactory;
use App\Models\MultipleChoiceOption;

use App\Utils\PrettyJson;
use Carbon\Carbon;

class QuestionnaireBackendController extends Controller {


    public function createQuestionByFormQuestion($formQuestion, $questionnaire) {
        $question = QuestionFactory::getInstance()->getQuestionByClassName($formQuestion->type);
        $question->setDescription($formQuestion->title);
        $question->setQuestionnaire($questionnaire);
        $question->save();

        foreach($formQuestion->options as $formOption) {
            $option = new MultipleChoiceOption();
            $option->setDescription($formOption->description);
            $option->setIsCorrectAnswer($formOption->isCorrect);
            $option->setIsOtherOption($formOption->isOtherOption);
            $option->setQuestion($question);
            $option->save();
        }

        return $question;
    }

    private function hashImageName($imageName) {
        return md5(Carbon::now()->toDateTimeString().$imageName);
    }

    /**
     * Symfony\Component\HttpFoundation\File\UploadedFile $file
     * $request->file('photo')
     * http://stackoverflow.com/questions/2704314/multiple-file-upload-in-php
     * http://www.w3bees.com/2013/02/multiple-file-upload-with-php.html
     */
    private function moveFile($file, $destinationPath) {
        $file->move($destinationPath, $this->hashImageName($file->getClientOriginalName()));
    }

    public function listAll() {
        $questionnaires = Questionnaire::all();
        return view("backend.questionnaires.list",
        ['questionnaires' => $questionnaires]);

    }

    public function report($id)
    {
        try
        {
            $questionnaire = Questionnaire::findOrFail($id);
            return PrettyJson::printPrettyJson($questionnaire->getReport());
        }
        catch (ModelNotFoundException $e)
        {
            abort(404);
        }
    }

    public function add() {
        return view("backend.questionnaires.add");
    }

    public function save(SaveQuestionnaireRequest $request) {

        $result = new \stdClass();
        $result->statusOk = true;

        try {
            $formQuestionnaire = json_decode($request->input("questionnaire"));
            $questionnaire = new Questionnaire();

            $questionnaire->setTitle($formQuestionnaire->title);
            $questionnaire->setDescription($formQuestionnaire->description);
            $questionnaire->setActiveFrom(Carbon::now());

            $questionnaire->save();

            foreach ($formQuestionnaire->questions as $formQuestion) {
                $this->createQuestionByFormQuestion($formQuestion, $questionnaire);
            }

        } catch(Exception $e) {
            $result->statusOk = false;
        } finally {
            return json_encode($result);
        }
    }

    /*
    Verifying File Presence

    You may also determine if a file is present on the request using the hasFile method:

    if ($request->hasFile('photo')) {
    }
    Validating Successful Uploads

    In addition to checking if the file is present, you may verify that there were no problems uploading the file via the isValid method:

    if ($request->file('photo')->isValid())
    {
    }
    */
}
