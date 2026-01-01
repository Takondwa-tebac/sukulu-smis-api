<?php

namespace App\Services;

use App\Models\ReportCard;
use App\Models\School;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class PdfService
{
    public function generateReportCard(ReportCard $reportCard): \Barryvdh\DomPDF\PDF
    {
        $reportCard->load([
            'student',
            'school',
            'academicYear',
            'term',
            'exam',
            'schoolClass',
            'stream',
            'subjects.subject',
        ]);

        $data = [
            'reportCard' => $reportCard,
            'school' => $reportCard->school,
            'student' => $reportCard->student,
            'academicYear' => $reportCard->academicYear,
            'term' => $reportCard->term,
            'subjects' => $reportCard->subjects,
            'generatedAt' => now(),
        ];

        return Pdf::loadView('pdf.report-card', $data)
            ->setPaper('a4', 'portrait');
    }

    public function generateBulkReportCards(Collection $reportCards): \Barryvdh\DomPDF\PDF
    {
        $reportCards->load([
            'student',
            'school',
            'academicYear',
            'term',
            'exam',
            'schoolClass',
            'stream',
            'subjects.subject',
        ]);

        $data = [
            'reportCards' => $reportCards,
            'generatedAt' => now(),
        ];

        return Pdf::loadView('pdf.report-cards-bulk', $data)
            ->setPaper('a4', 'portrait');
    }

    public function generateTranscript(Student $student, ?string $academicYearId = null): \Barryvdh\DomPDF\PDF
    {
        $student->load('school');

        $reportCardsQuery = ReportCard::where('student_id', $student->id)
            ->with(['academicYear', 'term', 'exam', 'schoolClass', 'subjects.subject'])
            ->orderBy('created_at');

        if ($academicYearId) {
            $reportCardsQuery->where('academic_year_id', $academicYearId);
        }

        $reportCards = $reportCardsQuery->get();

        $data = [
            'student' => $student,
            'school' => $student->school,
            'reportCards' => $reportCards,
            'generatedAt' => now(),
        ];

        return Pdf::loadView('pdf.transcript', $data)
            ->setPaper('a4', 'portrait');
    }

    public function generateClassReportSummary(
        string $examId,
        string $classId,
        ?string $streamId = null
    ): \Barryvdh\DomPDF\PDF {
        $reportCards = ReportCard::where('exam_id', $examId)
            ->where('class_id', $classId)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->with(['student', 'subjects.subject'])
            ->orderByDesc('average_score')
            ->get();

        $data = [
            'reportCards' => $reportCards,
            'generatedAt' => now(),
        ];

        return Pdf::loadView('pdf.class-report-summary', $data)
            ->setPaper('a4', 'landscape');
    }
}
