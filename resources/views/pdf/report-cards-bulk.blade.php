<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bulk Report Cards</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .page {
            padding: 15px;
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 10px;
        }
        .school-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
        }
        .school-motto {
            font-style: italic;
            color: #666;
            font-size: 9px;
        }
        .report-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 8px;
            background-color: #1e40af;
            color: white;
            padding: 4px 12px;
            display: inline-block;
        }
        .student-info {
            margin: 10px 0;
            font-size: 9px;
        }
        .info-row {
            display: table;
            width: 100%;
        }
        .info-cell {
            display: table-cell;
            width: 50%;
            padding: 2px 5px;
        }
        .info-label {
            font-weight: bold;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
        }
        .grades-table th,
        .grades-table td {
            border: 1px solid #ddd;
            padding: 5px;
        }
        .grades-table th {
            background-color: #1e40af;
            color: white;
            font-size: 8px;
        }
        .grades-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .grades-table .score,
        .grades-table .grade {
            text-align: center;
        }
        .summary-section {
            margin-top: 10px;
            padding: 10px;
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            width: 25%;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
        }
        .summary-label {
            font-size: 8px;
            color: #666;
        }
        .remarks-section {
            margin-top: 10px;
            font-size: 9px;
        }
        .remarks-box {
            border: 1px solid #ddd;
            padding: 5px;
            min-height: 30px;
            margin-top: 3px;
        }
        .signatures {
            margin-top: 15px;
            display: table;
            width: 100%;
            font-size: 8px;
        }
        .signature-box {
            display: table-cell;
            width: 33%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 25px;
            padding-top: 3px;
        }
    </style>
</head>
<body>
    @foreach($reportCards as $reportCard)
    <div class="page">
        <div class="header">
            <div class="school-name">{{ $reportCard->school->name }}</div>
            @if($reportCard->school->motto)
                <div class="school-motto">"{{ $reportCard->school->motto }}"</div>
            @endif
            <div class="report-title">Student Report Card</div>
        </div>

        <div class="student-info">
            <div class="info-row">
                <div class="info-cell">
                    <span class="info-label">Name:</span> {{ $reportCard->student->full_name }}
                </div>
                <div class="info-cell">
                    <span class="info-label">Adm No:</span> {{ $reportCard->student->admission_number }}
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell">
                    <span class="info-label">Class:</span> {{ $reportCard->schoolClass->name ?? 'N/A' }}
                    @if($reportCard->stream) ({{ $reportCard->stream->name }}) @endif
                </div>
                <div class="info-cell">
                    <span class="info-label">Term:</span> {{ $reportCard->term->name }} - {{ $reportCard->academicYear->name }}
                </div>
            </div>
        </div>

        <table class="grades-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Score</th>
                    <th>Grade</th>
                    <th>Points</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportCard->subjects as $index => $subject)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $subject->subject->name ?? 'Unknown' }}</td>
                    <td class="score">{{ number_format($subject->score, 1) }}</td>
                    <td class="grade">{{ $subject->grade }}</td>
                    <td class="score">{{ $subject->points }}</td>
                    <td>{{ $subject->remarks }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value">{{ number_format($reportCard->total_score, 1) }}</div>
                    <div class="summary-label">Total</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">{{ number_format($reportCard->average_score, 1) }}%</div>
                    <div class="summary-label">Average</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">{{ $reportCard->grade }}</div>
                    <div class="summary-label">Grade</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">{{ number_format($reportCard->gpa, 2) }}</div>
                    <div class="summary-label">GPA</div>
                </div>
            </div>
        </div>

        <div class="remarks-section">
            <strong>Class Teacher:</strong>
            <div class="remarks-box">{{ $reportCard->class_teacher_remarks ?? '' }}</div>
        </div>

        <div class="remarks-section">
            <strong>Head Teacher:</strong>
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
                <div class="signature-line">Stamp</div>
            </div>
        </div>
    </div>
    @endforeach
</body>
</html>
