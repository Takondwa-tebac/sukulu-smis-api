<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Academic Transcript - {{ $student->full_name }}</title>
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
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #1e40af;
            padding-bottom: 15px;
        }
        .school-name {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .school-address {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        .document-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
            text-transform: uppercase;
            color: #1e40af;
            letter-spacing: 2px;
        }
        .student-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .detail-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        .detail-cell {
            display: table-cell;
            width: 50%;
            padding: 3px 0;
        }
        .detail-label {
            font-weight: bold;
            color: #475569;
        }
        .term-section {
            margin: 20px 0;
            page-break-inside: avoid;
        }
        .term-header {
            background-color: #1e40af;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 11px;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
        }
        .results-table th,
        .results-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
        }
        .results-table th {
            background-color: #e2e8f0;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }
        .results-table .score,
        .results-table .grade,
        .results-table .points {
            text-align: center;
        }
        .term-summary {
            background-color: #f0f9ff;
            padding: 8px 12px;
            border: 1px solid #bae6fd;
            margin-top: -1px;
        }
        .summary-row {
            display: table;
            width: 100%;
        }
        .summary-cell {
            display: table-cell;
            width: 25%;
            text-align: center;
        }
        .summary-value {
            font-weight: bold;
            font-size: 12px;
            color: #1e40af;
        }
        .summary-label {
            font-size: 8px;
            color: #64748b;
        }
        .cumulative-section {
            margin-top: 25px;
            padding: 15px;
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
        }
        .cumulative-title {
            font-weight: bold;
            font-size: 12px;
            color: #92400e;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .cumulative-grid {
            display: table;
            width: 100%;
        }
        .cumulative-item {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 10px;
        }
        .cumulative-value {
            font-size: 18px;
            font-weight: bold;
            color: #92400e;
        }
        .cumulative-label {
            font-size: 9px;
            color: #78350f;
        }
        .certification {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #fafafa;
        }
        .certification-text {
            font-style: italic;
            text-align: justify;
        }
        .signatures {
            margin-top: 40px;
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
            margin-top: 50px;
            padding-top: 5px;
            font-size: 9px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(0, 0, 0, 0.03);
            font-weight: bold;
            z-index: -1;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="watermark">OFFICIAL TRANSCRIPT</div>
    
    <div class="container">
        <div class="header">
            <div class="school-name">{{ $school->name }}</div>
            @if($school->address || $school->city)
                <div class="school-address">
                    {{ $school->address }}{{ $school->city ? ', ' . $school->city : '' }}{{ $school->region ? ', ' . $school->region : '' }}
                </div>
            @endif
            @if($school->phone || $school->email)
                <div class="school-address">
                    {{ $school->phone }}{{ $school->email ? ' | ' . $school->email : '' }}
                </div>
            @endif
            <div class="document-title">Official Academic Transcript</div>
        </div>

        <div class="student-details">
            <div class="detail-row">
                <div class="detail-cell">
                    <span class="detail-label">Student Name:</span> {{ $student->full_name }}
                </div>
                <div class="detail-cell">
                    <span class="detail-label">Admission Number:</span> {{ $student->admission_number }}
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-cell">
                    <span class="detail-label">Date of Birth:</span> {{ $student->date_of_birth?->format('F j, Y') ?? 'N/A' }}
                </div>
                <div class="detail-cell">
                    <span class="detail-label">Gender:</span> {{ ucfirst($student->gender ?? 'N/A') }}
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-cell">
                    <span class="detail-label">Date of Admission:</span> {{ $student->admission_date?->format('F j, Y') ?? 'N/A' }}
                </div>
                <div class="detail-cell">
                    <span class="detail-label">Transcript Date:</span> {{ $generatedAt->format('F j, Y') }}
                </div>
            </div>
        </div>

        @php
            $totalGPA = 0;
            $termCount = 0;
        @endphp

        @foreach($reportCards->groupBy('academic_year_id') as $academicYearId => $yearReports)
            @php
                $firstReport = $yearReports->first();
            @endphp
            
            <h3 style="margin-top: 20px; color: #1e40af; border-bottom: 1px solid #1e40af; padding-bottom: 5px;">
                Academic Year: {{ $firstReport->academicYear->name ?? 'N/A' }}
            </h3>

            @foreach($yearReports as $reportCard)
                @php
                    $totalGPA += $reportCard->gpa ?? 0;
                    $termCount++;
                @endphp
                
                <div class="term-section">
                    <div class="term-header">
                        {{ $reportCard->term->name ?? 'Term' }} - {{ $reportCard->schoolClass->name ?? 'Class' }}
                        @if($reportCard->exam)
                            | {{ $reportCard->exam->name }}
                        @endif
                    </div>
                    
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 50%;">Subject</th>
                                <th style="width: 15%;">Score</th>
                                <th style="width: 10%;">Grade</th>
                                <th style="width: 10%;">Points</th>
                                <th style="width: 10%;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportCard->subjects as $index => $subject)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $subject->subject->name ?? 'Unknown' }}</td>
                                <td class="score">{{ number_format($subject->score, 1) }}</td>
                                <td class="grade">{{ $subject->grade }}</td>
                                <td class="points">{{ $subject->points }}</td>
                                <td>{{ $subject->remarks }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="term-summary">
                        <div class="summary-row">
                            <div class="summary-cell">
                                <div class="summary-value">{{ number_format($reportCard->total_score, 1) }}</div>
                                <div class="summary-label">Total Score</div>
                            </div>
                            <div class="summary-cell">
                                <div class="summary-value">{{ number_format($reportCard->average_score, 1) }}%</div>
                                <div class="summary-label">Average</div>
                            </div>
                            <div class="summary-cell">
                                <div class="summary-value">{{ $reportCard->grade }}</div>
                                <div class="summary-label">Grade</div>
                            </div>
                            <div class="summary-cell">
                                <div class="summary-value">{{ number_format($reportCard->gpa, 2) }}</div>
                                <div class="summary-label">GPA</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach

        @if($termCount > 0)
        <div class="cumulative-section">
            <div class="cumulative-title">Cumulative Performance</div>
            <div class="cumulative-grid">
                <div class="cumulative-item">
                    <div class="cumulative-value">{{ $termCount }}</div>
                    <div class="cumulative-label">Terms Completed</div>
                </div>
                <div class="cumulative-item">
                    <div class="cumulative-value">{{ number_format($totalGPA / $termCount, 2) }}</div>
                    <div class="cumulative-label">Cumulative GPA</div>
                </div>
                <div class="cumulative-item">
                    <div class="cumulative-value">{{ $reportCards->sum('total_subjects') }}</div>
                    <div class="cumulative-label">Total Subject Entries</div>
                </div>
            </div>
        </div>
        @endif

        <div class="certification">
            <div class="certification-text">
                This is to certify that the above is a true and accurate record of the academic performance of 
                <strong>{{ $student->full_name }}</strong> (Admission No: {{ $student->admission_number }}) 
                at {{ $school->name }}. This transcript is issued upon request and is valid for official purposes.
            </div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">Registrar/Academic Officer</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Head Teacher/Principal</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Official School Stamp</div>
            </div>
        </div>

        <div class="footer">
            <p>Document Reference: TRANS-{{ $student->id }}-{{ $generatedAt->format('YmdHis') }}</p>
            <p>Generated on {{ $generatedAt->format('F j, Y \a\t g:i A') }} | {{ $school->name }}</p>
            <p>This document is only valid with official school stamp and authorized signatures.</p>
        </div>
    </div>
</body>
</html>
