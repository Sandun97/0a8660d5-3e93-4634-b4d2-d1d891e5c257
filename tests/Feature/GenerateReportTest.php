<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateReportTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Fake the storage
        Storage::fake('local');

        // Students JSON
        $students = [
            ['id' => 'student1', 'firstName' => 'Tony', 'lastName' => 'Stark', 'yearLevel' => 6],
            ['id' => 'student2', 'firstName' => 'Steve', 'lastName' => 'Rogers', 'yearLevel' => 6],
            ['id' => 'student3', 'firstName' => 'Peter', 'lastName' => 'Parker', 'yearLevel' => 6]
        ];

        // Assessments JSON
        $assessments = [
            [
                'id' => 'assessment1',
                'name' => 'Numeracy',
                'questions' => [['questionId' => 'numeracy1', 'position' => 1]]
            ]
        ];

        // Questions JSON
        $questions = [
            [
                'id' => 'numeracy1',
                'stem' => 'What is the value of 2 + 3 x 5?',
                'type' => 'multiple-choice',
                'strand' => 'Number and Algebra',
                'config' => [
                    'options' => [
                        ['id' => 'option1', 'label' => 'A', 'value' => '10'],
                        ['id' => 'option2', 'label' => 'B', 'value' => '15'],
                        ['id' => 'option3', 'label' => 'C', 'value' => '17'],
                        ['id' => 'option4', 'label' => 'D', 'value' => '25']
                    ],
                    'key' => 'option3',
                    'hint' => 'Work out the multiplication sign BEFORE the addition sign'
                ]
            ]
        ];

        // Student responses JSON
        $responses = [
            [
                'id' => 'studentReponse1',
                'assessmentId' => 'assessment1',
                'completed' => '2025-08-23',
                'student' => ['id' => 'student1', 'yearLevel' => 6],
                'responses' => [
                    ['questionId' => 'numeracy1', 'response' => 'option3']
                ],
                'results' => ['rawScore' => 1]
            ]
        ];

        // Save all fake JSON files to storage
        Storage::put('data/students.json', json_encode($students));
        Storage::put('data/assessments.json', json_encode($assessments));
        Storage::put('data/questions.json', json_encode($questions));
        Storage::put('data/student-responses.json', json_encode($responses));
    }

    public function testGenerateDiagnosticReport()
    {
        $this->artisan('report:generate', ['studentId' => 'student1', 'type' => 'diagnostic'])
            ->expectsOutputToContain('Tony Stark recently completed Numeracy assessment')
            ->assertExitCode(0);
    }

    public function testGenerateProgressReport()
    {
        $this->artisan('report:generate', ['studentId' => 'student1', 'type' => 'progress'])
            ->expectsOutputToContain('Tony Stark has completed Numeracy assessment')
            ->assertExitCode(0);
    }

    public function testGenerateFeedbackReport()
    {
        $this->artisan('report:generate', ['studentId' => 'student1', 'type' => 'feedback'])
            ->expectsOutputToContain('Feedback for wrong answers given below')
            ->assertExitCode(0);
    }

    public function testInvalidStudentId()
    {
        $this->artisan('report:generate', ['studentId' => 'unknown', 'type' => 'diagnostic'])
            ->expectsOutputToContain('Student not found.')
            ->assertExitCode(0);
    }

    public function testInvalidReportType()
    {
        $this->artisan('report:generate', ['studentId' => 'student1', 'type' => 'unknown'])
            ->expectsOutputToContain('Unknown report type. Use: diagnostic, progress, or feedback.')
            ->assertExitCode(0);
    }
}
