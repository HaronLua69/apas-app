<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Support\ApapReportBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ApapReportController extends Controller
{
    public function __invoke(Request $request, ApapReportBuilder $builder): View
    {
        $filters = $this->validatedFilters($request);

        return view('adviser.reports.apap', $builder->build($request->user(), $filters));
    }

    public function export(Request $request, ApapReportBuilder $builder): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $reportData = $builder->build($request->user(), $filters);
        $filename = 'apap-report-'.$reportData['filters']['academic_year'].'.csv';

        return response()->streamDownload(function () use ($reportData): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            $this->writeCsvRow($handle, ['Academic Program Advising Progress (APAP) Report']);
            $this->writeCsvRow($handle, ['Academic Year', $reportData['filters']['academic_year']]);

            $selectedCourse = $reportData['courseOptions']->firstWhere('id', $reportData['filters']['course_id']);
            $selectedMajor = $reportData['majorOptions']->firstWhere('id', $reportData['filters']['major_id']);

            $this->writeCsvRow($handle, ['Course', $selectedCourse?->name ?? 'All Courses']);
            $this->writeCsvRow($handle, ['Major', $selectedMajor?->name ?? 'All Majors']);
            $this->writeCsvRow($handle, ['Year Level', $reportData['filters']['year_level'] === null ? 'All Year Levels' : 'Year '.$reportData['filters']['year_level']]);
            $this->writeCsvRow($handle, []);
            $this->writeCsvRow($handle, ['Metric', 'Value']);

            foreach ($this->summaryMetrics($reportData['report']) as [$label, $value]) {
                $this->writeCsvRow($handle, [$label, $value]);
            }

            $this->writeCsvRow($handle, []);
            $this->writeCsvRow($handle, [
                'Student',
                'ID Number',
                'Academic Year GPA',
                'End-of-Academic Year GPA',
                'Cumulative GPA',
                'Completed',
                'Promoted',
                'Dropped Subject',
                'Has INC',
                'Withdrew',
                'Failing Grade',
                'Award',
            ]);

            foreach ($reportData['report']['rows'] as $row) {
                $this->writeCsvRow($handle, [
                    $row['displayName'],
                    $row['studentProfile']->user?->id_number,
                    $this->formatDecimal($row['academicYearGpa']),
                    $this->formatDecimal($row['endOfAcademicYearGpa']),
                    $this->formatDecimal($row['cumulativeGpa']),
                    $row['countsAsCompleted'] ? 'Yes' : 'No',
                    $row['promoted'] ? 'Yes' : 'No',
                    $row['droppedAnySubject'] ? 'Yes' : 'No',
                    $row['hasUnresolvedInc'] ? 'Yes' : 'No',
                    $row['withdrewFromProgram'] ? 'Yes' : 'No',
                    $row['hasFailingGrade'] ? 'Yes' : 'No',
                    $row['award'] ?? 'None',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'academic_year' => ['nullable', 'string'],
            'course_id' => ['nullable', 'integer'],
            'major_id' => ['nullable', 'integer'],
            'year_level' => ['nullable', 'integer', 'between:1,4'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array{0:string,1:string|int}>
     */
    private function summaryMetrics(array $report): array
    {
        return [
            ['Total Program Enrollees', $report['totalProgramEnrollees']],
            ['Survival Rate', $this->formatRate($report['survivalRate'])],
            ['Completion Rate', $this->formatRate($report['completionRate'])],
            ['Promotion Rate', $this->formatRate($report['promotionRate'])],
            ['Failure Rate', $this->formatRate($report['failureRate'])],
            ['Dropout Rate', $this->formatRate($report['dropoutRate'])],
            ['Average Academic Year GPA', $this->formatDecimal($report['averageAcademicYearGpa'])],
            ['Average CGPA of Students', $this->formatDecimal($report['averageCumulativeGpa'])],
            ['Number of Students with INC', $report['studentsWithInc']],
            ['Number of Students who withdrew from the program', $report['studentsWithdrawn']],
            ['Number of Students with failing grades', $report['studentsWithFailingGrades']],
            ['Rizal Excellence Awardees', $report['rizalAwardees']],
            ['Chancellor Excellence Awardees', $report['chancellorAwardees']],
            ['Dean Excellence Awardees', $report['deanAwardees']],
        ];
    }

    /**
     * @param  resource  $handle
     * @param  array<int, mixed>  $row
     */
    private function writeCsvRow($handle, array $row): void
    {
        fputcsv($handle, $row);
    }

    private function formatRate(?float $value): string
    {
        return $value === null ? 'N/A' : number_format($value, 2).'%';
    }

    private function formatDecimal(?float $value): string
    {
        return $value === null ? 'N/A' : number_format($value, 5, '.', '');
    }
}
