<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateReport extends Command
{
    protected $signature = 'report:generate {studentId} {type}';
    protected $description = 'Generate diagnostic, progress, or feedback reports for a student';

    public function handle()
    {
        $studentId = $this->argument('studentId');
        $type = $this->argument('type');

        $students = $this->loadJson('students.json');
        $questions = $this->loadJson('questions.json');
        $assessments = $this->loadJson('assessments.json');
        $responses = $this->loadJson('student-responses.json');

        $student = collect($students)->firstWhere('id', $studentId);
        if (!$student) {
            $this->error("Student not found.");
            return;
        }

        switch ($type) {
            case 'diagnostic':
                $this->generateDiagnostic($student, $responses, $questions, $assessments);
                break;
            case 'progress':
                $this->generateProgress($student, $responses, $assessments);
                break;
            case 'feedback':
                $this->generateFeedback($student, $responses, $questions, $assessments);
                break;
            default:
                $this->error("Unknown report type. Use: diagnostic, progress, or feedback.");
        }
    }

    private function loadJson($filename)
    {
        $path = storage_path("app/data/{$filename}");
        if (!File::exists($path)) {
            $this->error("File not found: {$filename}");
            return [];
        }
        return json_decode(File::get($path), true);
    }

    private function generateDiagnostic($student, $responses, $questions, $assessments)
    {
        $studentResponses = collect($responses)
            ->where('student.id', $student['id'])
            ->sortByDesc('completed')
            ->first();

        if (!$studentResponses) {
            $this->warn("No responses found.");
            return;
        }

        $assessment = collect($assessments)->firstWhere('id', $studentResponses['assessmentId']);
        $completedAt = $studentResponses['completed'];

        $correctCount = 0;
        $strandSummary = [];

        foreach ($studentResponses['responses'] as $resp) {
            $question = collect($questions)->firstWhere('id', $resp['questionId']);
            if (!$question) continue;

            $isCorrect = $resp['response'] === $question['config']['key'];
            if ($isCorrect) $correctCount++;

            $strand = $question['strand'];
            if (!isset($strandSummary[$strand])) {
                $strandSummary[$strand] = ['correct' => 0, 'total' => 0];
            }
            $strandSummary[$strand]['total']++;
            if ($isCorrect) $strandSummary[$strand]['correct']++;
        }

        $this->line("{$student['firstName']} {$student['lastName']} recently completed {$assessment['name']} assessment on {$completedAt}");
        $this->line("He got {$correctCount} questions right out of " . count($studentResponses['responses']) . ". Details by strand given below:\n");

        foreach ($strandSummary as $strand => $summary) {
            $this->line("{$strand}: {$summary['correct']} out of {$summary['total']} correct");
        }
    }

    private function generateProgress($student, $responses, $assessments)
    {
        $studentResponses = collect($responses)
            ->where('student.id', $student['id'])
            ->sortBy('completed');

        if ($studentResponses->isEmpty()) {
            $this->warn("No responses found.");
            return;
        }

        $assessment = collect($assessments)->firstWhere('id', $studentResponses->first()['assessmentId']);

        $this->line("{$student['firstName']} {$student['lastName']} has completed {$assessment['name']} assessment {$studentResponses->count()} times in total. Date and raw score given below:\n");

        foreach ($studentResponses as $resp) {
            $date = $resp['completed'] ?? 'N/A';
            $this->line("Date: {$date}, Raw Score: {$resp['results']['rawScore']} out of " . count($resp['responses']));
        }

        $first = $studentResponses->first()['results']['rawScore'];
        $last = $studentResponses->last()['results']['rawScore'];
        $diff = $last - $first;

        $this->line("\n{$student['firstName']} {$student['lastName']} got {$diff} more correct in the recent completed assessment than the oldest");
    }

    private function generateFeedback($student, $responses, $questions, $assessments)
    {
        $studentResponses = collect($responses)
            ->where('student.id', $student['id'])
            ->sortByDesc('completed')
            ->first();

        if (!$studentResponses) {
            $this->warn("No responses found.");
            return;
        }

        $assessment = collect($assessments)->firstWhere('id', $studentResponses['assessmentId']);
        $completedAt = $studentResponses['completed'];

        $correctCount = 0;
        $totalQuestions = count($studentResponses['responses']);

        foreach ($studentResponses['responses'] as $resp) {
            $question = collect($questions)->firstWhere('id', $resp['questionId']);
            if (!$question) continue;
            if ($resp['response'] === $question['config']['key']) $correctCount++;
        }

        $this->line("{$student['firstName']} {$student['lastName']} recently completed {$assessment['name']} assessment on {$completedAt}");
        $this->line("He got {$correctCount} questions right out of {$totalQuestions}. Feedback for wrong answers given below\n");

        foreach ($studentResponses['responses'] as $resp) {
            $question = collect($questions)->firstWhere('id', $resp['questionId']);
            if (!$question) continue;

            if ($resp['response'] !== $question['config']['key']) {
                $yourAns = collect($question['config']['options'])->firstWhere('id', $resp['response']);
                $correctAns = collect($question['config']['options'])->firstWhere('id', $question['config']['key']);

                $this->line("Question: {$question['stem']}");
                $this->line("Your answer: {$yourAns['label']} with value {$yourAns['value']}");
                $this->line("Right answer: {$correctAns['label']} with value {$correctAns['value']}");
                $this->line("Hint: {$question['config']['hint']}\n");
            }
        }
    }
}
