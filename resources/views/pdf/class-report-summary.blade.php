<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Class Report Summary</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
        }
        .container {
            padding: 15px;
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
        }
        .report-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 8px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .summary-table th,
        .summary-table td {
            border: 1px solid #ddd;
            padding: 5px 8px;
            text-align: left;
        }
        .summary-table th {
            background-color: #1e40af;
            color: white;
            font-size: 8px;
        }
        .summary-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .summary-table .rank {
            text-align: center;
            font-weight: bold;
        }
        .summary-table .score {
            text-align: center;
        }
        .summary-table .grade {
            text-align: center;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if($reportCards->first())
                <div class="school-name">{{ $reportCards->first()->school->name ?? 'School' }}</div>
            @endif
            <div class="report-title">Class Performance Summary Report</div>
            <div style="font-size: 10px; margin-top: 5px;">
                Generated: {{ $generatedAt->format('F j, Y') }}
            </div>
        </div>

        <table class="summary-table">
            <thead>
                <tr>
                    <th style="width: 5%;">Rank</th>
                    <th style="width: 8%;">Adm No</th>
                    <th style="width: 22%;">Student Name</th>
                    <th style="width: 10%;">Total</th>
                    <th style="width: 10%;">Average</th>
                    <th style="width: 8%;">Grade</th>
                    <th style="width: 8%;">GPA</th>
                    <th style="width: 8%;">Subjects</th>
                    <th style="width: 21%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportCards as $index => $card)
                <tr>
                    <td class="rank">{{ $index + 1 }}</td>
                    <td>{{ $card->student->admission_number ?? 'N/A' }}</td>
                    <td>{{ $card->student->full_name ?? 'Unknown' }}</td>
                    <td class="score">{{ number_format($card->total_score, 1) }}</td>
                    <td class="score">{{ number_format($card->average_score, 1) }}%</td>
                    <td class="grade">{{ $card->grade }}</td>
                    <td class="score">{{ number_format($card->gpa, 2) }}</td>
                    <td class="score">{{ $card->total_subjects }}</td>
                    <td>{{ Str::limit($card->class_teacher_remarks, 30) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px; padding: 10px; background-color: #f0f9ff; border: 1px solid #bae6fd;">
            <strong>Summary Statistics:</strong>
            <div style="display: table; width: 100%; margin-top: 10px;">
                <div style="display: table-cell; width: 20%; text-align: center;">
                    <div style="font-size: 14px; font-weight: bold; color: #1e40af;">{{ $reportCards->count() }}</div>
                    <div style="font-size: 8px;">Total Students</div>
                </div>
                <div style="display: table-cell; width: 20%; text-align: center;">
                    <div style="font-size: 14px; font-weight: bold; color: #1e40af;">{{ number_format($reportCards->avg('average_score'), 1) }}%</div>
                    <div style="font-size: 8px;">Class Average</div>
                </div>
                <div style="display: table-cell; width: 20%; text-align: center;">
                    <div style="font-size: 14px; font-weight: bold; color: #1e40af;">{{ number_format($reportCards->max('average_score'), 1) }}%</div>
                    <div style="font-size: 8px;">Highest Score</div>
                </div>
                <div style="display: table-cell; width: 20%; text-align: center;">
                    <div style="font-size: 14px; font-weight: bold; color: #1e40af;">{{ number_format($reportCards->min('average_score'), 1) }}%</div>
                    <div style="font-size: 8px;">Lowest Score</div>
                </div>
                <div style="display: table-cell; width: 20%; text-align: center;">
                    <div style="font-size: 14px; font-weight: bold; color: #1e40af;">{{ number_format($reportCards->avg('gpa'), 2) }}</div>
                    <div style="font-size: 8px;">Average GPA</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>This report is computer-generated and is for internal use only.</p>
        </div>
    </div>
</body>
</html>
