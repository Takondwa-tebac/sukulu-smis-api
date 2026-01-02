<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #1a1a1a;
            background: #fff;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #2563eb;
            letter-spacing: -0.5px;
        }
        .logo-subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            font-size: 32px;
            font-weight: 300;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .invoice-number {
            font-size: 14px;
            color: #6b7280;
            margin-top: 8px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 8px;
        }
        .status-draft { background: #f3f4f6; color: #4b5563; }
        .status-sent { background: #dbeafe; color: #1d4ed8; }
        .status-paid { background: #d1fae5; color: #059669; }
        .status-overdue { background: #fee2e2; color: #dc2626; }
        
        .billing-section {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .billing-from, .billing-to {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .billing-label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .billing-name {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        .billing-details {
            font-size: 13px;
            color: #4b5563;
            line-height: 1.6;
        }
        
        .invoice-meta {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .meta-row {
            display: table;
            width: 100%;
        }
        .meta-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px;
        }
        .meta-label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-value {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-top: 4px;
        }
        .meta-value.overdue {
            color: #dc2626;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #f9fafb;
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        .items-table td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .item-description {
            font-weight: 500;
            color: #1a1a1a;
        }
        .item-details {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .totals-section {
            margin-left: auto;
            width: 300px;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
        }
        .totals-label {
            display: table-cell;
            text-align: left;
            color: #6b7280;
        }
        .totals-value {
            display: table-cell;
            text-align: right;
            font-weight: 500;
        }
        .totals-row.total {
            border-top: 2px solid #e5e7eb;
            margin-top: 8px;
            padding-top: 16px;
        }
        .totals-row.total .totals-label,
        .totals-row.total .totals-value {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
        }
        .totals-row.balance {
            background: #fef3c7;
            margin: 8px -16px 0;
            padding: 12px 16px;
            border-radius: 6px;
        }
        .totals-row.balance .totals-label,
        .totals-row.balance .totals-value {
            font-weight: 700;
            color: #92400e;
        }
        
        .notes-section {
            margin-top: 40px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .notes-title {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .notes-content {
            font-size: 13px;
            color: #4b5563;
        }
        
        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
        .footer-contact {
            margin-top: 8px;
        }
        
        .payment-info {
            margin-top: 30px;
            padding: 20px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
        }
        .payment-title {
            font-size: 13px;
            font-weight: 600;
            color: #1d4ed8;
            margin-bottom: 12px;
        }
        .payment-details {
            font-size: 13px;
            color: #1e40af;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="logo-section">
                <div class="logo">Sukulu</div>
                <div class="logo-subtitle">School Management System</div>
            </div>
            <div class="invoice-title">
                <h1>Invoice</h1>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
            </div>
        </div>

        <div class="billing-section">
            <div class="billing-from">
                <div class="billing-label">From</div>
                <div class="billing-name">Sukulu SMIS</div>
                <div class="billing-details">
                    School Management Platform<br>
                    support@sukulu.com
                </div>
            </div>
            <div class="billing-to">
                <div class="billing-label">Bill To</div>
                <div class="billing-name">{{ $school->name }}</div>
                <div class="billing-details">
                    @if($school->address){{ $school->address }}<br>@endif
                    @if($school->city){{ $school->city }}@endif @if($school->region), {{ $school->region }}@endif<br>
                    @if($school->email){{ $school->email }}@endif
                </div>
            </div>
        </div>

        <div class="invoice-meta">
            <div class="meta-row">
                <div class="meta-item">
                    <div class="meta-label">Invoice Date</div>
                    <div class="meta-value">{{ $invoice->invoice_date->format('M d, Y') }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Due Date</div>
                    <div class="meta-value {{ $invoice->isOverdue() ? 'overdue' : '' }}">
                        {{ $invoice->due_date->format('M d, Y') }}
                    </div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Amount Due</div>
                    <div class="meta-value">{{ $invoice->currency }} {{ number_format($invoice->balance, 2) }}</div>
                </div>
            </div>
        </div>

        @if($invoice->description)
        <p style="margin-bottom: 20px; color: #4b5563;">{{ $invoice->description }}</p>
        @endif

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%;">Qty</th>
                    <th style="width: 17%;">Unit Price</th>
                    <th style="width: 18%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>
                        <div class="item-description">{{ $item->description }}</div>
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $invoice->currency }} {{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $invoice->currency }} {{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <div class="totals-row">
                <span class="totals-label">Subtotal</span>
                <span class="totals-value">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="totals-row">
                <span class="totals-label">Discount{{ $invoice->discount_reason ? " ({$invoice->discount_reason})" : '' }}</span>
                <span class="totals-value">-{{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</span>
            </div>
            @endif
            <div class="totals-row total">
                <span class="totals-label">Total</span>
                <span class="totals-value">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</span>
            </div>
            @if($invoice->amount_paid > 0)
            <div class="totals-row">
                <span class="totals-label">Amount Paid</span>
                <span class="totals-value">-{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</span>
            </div>
            @endif
            @if($invoice->balance > 0)
            <div class="totals-row balance">
                <span class="totals-label">Amount Due</span>
                <span class="totals-value">{{ $invoice->currency }} {{ number_format($invoice->balance, 2) }}</span>
            </div>
            @endif
        </div>

        @if($invoice->notes)
        <div class="notes-section">
            <div class="notes-title">Notes</div>
            <div class="notes-content">{{ $invoice->notes }}</div>
        </div>
        @endif

        <div class="payment-info">
            <div class="payment-title">Payment Information</div>
            <div class="payment-details">
                Please make payment to the following account:<br>
                <strong>Bank:</strong> National Bank of Malawi<br>
                <strong>Account Name:</strong> Sukulu Technologies Ltd<br>
                <strong>Account Number:</strong> 1234567890<br>
                <strong>Reference:</strong> {{ $invoice->invoice_number }}
            </div>
        </div>

        <div class="footer">
            <div>Thank you for your business!</div>
            <div class="footer-contact">
                Questions? Contact us at support@sukulu.com
            </div>
        </div>
    </div>
</body>
</html>
