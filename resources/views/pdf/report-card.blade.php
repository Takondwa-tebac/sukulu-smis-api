<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card - {{ $student->full_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 15px;
        }
        .school-logo {
            max-height: 60px;
            margin-bottom: 10px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
        }
        .school-motto {
            font-style: italic;
            color: #666;
            margin-top: 5px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            text-transform: uppercase;
            background-color: #1e40af;
            color: white;
            padding: 5px 15px;
            display: inline-block;
        }
        .student-info {
            margin: 15px 0;
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-cell {
            display: table-cell;
            padding: 4px 10px;
            width: 50%;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .grades-table th,
        .grades-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .grades-table th {
            background-color: #1e40af;
            color: white;
            font-weight: bold;
        }
        .grades-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .grades-table .score {
            text-align: center;
            font-weight: bold;
        }
        .grades-table .grade {
            text-align: center;
            font-weight: bold;
        }
        .summary-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 5px;
        }
        .summary-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            color: #1e40af;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
            width: 25%;
        }
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }
        .summary-label {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
        }
        .remarks-section {
            margin-top: 20px;
        }
        .remarks-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 10px;
            min-height: 50px;
        }
        .remarks-label {
            font-weight: bold;
            color: #555;
        }
        .signatures {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 10px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .grade-key {
            margin-top: 15px;
            font-size: 9px;
        }
        .grade-key-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .grade-key-items {
            display: table;
            width: 100%;
        }
        .grade-key-item {
            display: table-cell;
            padding: 2px 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if($school->getFirstMediaUrl('logo'))
                <img src="{{ $school->getFirstMediaUrl('logo') }}" alt="School Logo" class="school-logo">
            @endif
            <div class="school-name">{{ $school->name }}</div>
            @if($school->motto)
                <div class="school-motto">"{{ $school->motto }}"</div>
            @endif
            <div class="report-title">Student Report Card</div>
        </div>

        <div class="student-info">
            <div class="info-row">
                <div class="info-cell">
                    <span class="info-label">Student Name:</span> {{ $student->full_name }}
                </div>
                <div class="info-cell">
                    <span class="info-label">Admission No:</span> {{ $student->admission_number }}
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell">
                    <span class="info-label">Class:</span> {{ $reportCard->schoolClass->name ?? 'N/A' }}
                    @if($reportCard->stream)
                        ({{ $reportCard->stream->name }})
                    @endif
                </div>
                <div class="info-cell">
                    <span class="info-label">Academic Year:</span> {{ $academicYear->name }}
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell">
                    <span class="info-label">Term:</span> {{ $term->name }}
                </div>
                <div class="info-cell">
                    <span class="info-label">Exam:</span> {{ $reportCard->exam->name ?? 'N/A' }}
                </div>
            </div>
        </div>

        <table class="grades-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">Subject</th>
                    <th style="width: 15%;">Score</th>
                    <th style="width: 10%;">Grade</th>
                    <th style="width: 10%;">Points</th>
                    <th style="width: 15%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subjects as $index => $subjectResult)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $subjectResult->subject->name ?? 'Unknown Subject' }}</td>
                    <td class="score">{{ number_format($subjectResult->score, 1) }}</td>
                    <td class="grade">{{ $subjectResult->grade }}</td>
                    <td class="score">{{ $subjectResult->points }}</td>
                    <td>{{ $subjectResult->remarks }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-section">
            <div class="summary-title">Performance Summary</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value">{{ number_format($reportCard->total_score, 1) }}</div>
                    <div class="summary-label">Total Score</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">{{ number_format($reportCard->average_score, 1) }}%</div>
                    <div class="summary-label">Average</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">{{ $reportCard->grade }}</div>
                    <div class="summary-label">Overall Grade</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">{{ number_format($reportCard->gpa, 2) }}</div>
                    <div class="summary-label">GPA</div>
                </div>
            </div>
            @if($reportCard->position)
            <div style="text-align: center; margin-top: 10px;">
                <span class="info-label">Class Position:</span> {{ $reportCard->position }} out of {{ $reportCard->total_students ?? 'N/A' }}
            </div>
            @endif
        </div>

        <div class="remarks-section">
            <div class="remarks-label">Class Teacher's Remarks:</div>
            <div class="remarks-box">{{ $reportCard->class_teacher_remarks ?? '' }}</div>
        </div>

        <div class="remarks-section">
            <div class="remarks-label">Head Teacher's Remarks:</div>
            <div class="remarks-box">{{ $reportCard->head_teacher_remarks ?? '' }}</div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">Class Teacher</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Head Teacher</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">School Stamp</div>
            </div>
        </div>

        <div class="grade-key">
            <div class="grade-key-title">Grading Key:</div>
            <div class="grade-key-items">
                <span class="grade-key-item">A (75-100) - Excellent</span>
                <span class="grade-key-item">B (65-74) - Very Good</span>
                <span class="grade-key-item">C (55-64) - Good</span>
                <span class="grade-key-item">D (45-54) - Satisfactory</span>
                <span class="grade-key-item">E (35-44) - Pass</span>
                <span class="grade-key-item">F (0-34) - Fail</span>
            </div>
        </div>

        <div class="footer">
            <p>Generated on {{ $generatedAt->format('F j, Y \a\t g:i A') }} | {{ $school->name }}</p>
            <p>This is a computer-generated document. No signature is required for authenticity.</p>
        </div>
    </div>
</body>
</html>
