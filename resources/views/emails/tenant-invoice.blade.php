<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #1a1a1a;
            background-color: #f6f9fc;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 32px 40px;
            text-align: center;
        }
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .email-body {
            padding: 40px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 16px;
        }
        .intro-text {
            font-size: 15px;
            color: #525f7f;
            margin-bottom: 32px;
        }
        .invoice-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
        }
        .invoice-header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .invoice-number-section {
            display: table-cell;
            vertical-align: top;
        }
        .invoice-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .invoice-number {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-top: 4px;
        }
        .invoice-amount-section {
            display: table-cell;
            text-align: right;
            vertical-align: top;
        }
        .amount-due {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
        }
        .due-date {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
        .due-date.overdue {
            color: #dc2626;
            font-weight: 600;
        }
        .invoice-items {
            margin-top: 20px;
        }
        .item-row {
            display: table;
            width: 100%;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .item-row:last-child {
            border-bottom: none;
        }
        .item-description {
            display: table-cell;
            font-size: 14px;
            color: #1a1a1a;
        }
        .item-amount {
            display: table-cell;
            text-align: right;
            font-size: 14px;
            font-weight: 500;
            color: #1a1a1a;
        }
        .item-qty {
            font-size: 12px;
            color: #64748b;
        }
        .totals-section {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 2px solid #e2e8f0;
        }
        .total-row {
            display: table;
            width: 100%;
            padding: 8px 0;
        }
        .total-label {
            display: table-cell;
            font-size: 14px;
            color: #64748b;
        }
        .total-value {
            display: table-cell;
            text-align: right;
            font-size: 14px;
            font-weight: 500;
            color: #1a1a1a;
        }
        .total-row.grand-total {
            padding-top: 12px;
        }
        .total-row.grand-total .total-label,
        .total-row.grand-total .total-value {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
        }
        .cta-section {
            text-align: center;
            margin: 32px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
        }
        .payment-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin-top: 24px;
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
        .email-footer {
            padding: 24px 40px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        .footer-text {
            font-size: 13px;
            color: #64748b;
        }
        .footer-links {
            margin-top: 12px;
        }
        .footer-links a {
            color: #2563eb;
            text-decoration: none;
            font-size: 13px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 8px;
        }
        .status-sent { background: #dbeafe; color: #1d4ed8; }
        .status-overdue { background: #fee2e2; color: #dc2626; }
        .status-partially_paid { background: #fef3c7; color: #d97706; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <div class="logo">Sukulu</div>
            </div>
            
            <div class="email-body">
                <div class="greeting">Hi {{ $invoice->school->name }},</div>
                
                @if($invoice->isOverdue())
                <p class="intro-text">
                    This is a reminder that your invoice is now <strong>overdue</strong>. Please make payment at your earliest convenience to avoid any service interruptions.
                </p>
                @else
                <p class="intro-text">
                    Here's your invoice for your Sukulu subscription. Please find the details below and the PDF invoice attached.
                </p>
                @endif

                <div class="invoice-card">
                    <div class="invoice-header">
                        <div class="invoice-number-section">
                            <div class="invoice-label">Invoice Number</div>
                            <div class="invoice-number">
                                {{ $invoice->invoice_number }}
                                @if($invoice->isOverdue())
                                <span class="status-badge status-overdue">Overdue</span>
                                @elseif($invoice->status === 'partially_paid')
                                <span class="status-badge status-partially_paid">Partial</span>
                                @endif
                            </div>
                        </div>
                        <div class="invoice-amount-section">
                            <div class="amount-due">{{ $invoice->currency }} {{ number_format($invoice->balance, 2) }}</div>
                            <div class="due-date {{ $invoice->isOverdue() ? 'overdue' : '' }}">
                                @if($invoice->isOverdue())
                                    Was due {{ $invoice->due_date->format('M d, Y') }}
                                @else
                                    Due {{ $invoice->due_date->format('M d, Y') }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="invoice-items">
                        @foreach($invoice->items as $item)
                        <div class="item-row">
                            <span class="item-description">
                                {{ $item->description }}
                                @if($item->quantity > 1)
                                <span class="item-qty">(× {{ $item->quantity }})</span>
                                @endif
                            </span>
                            <span class="item-amount">{{ $invoice->currency }} {{ number_format($item->amount, 2) }}</span>
                        </div>
                        @endforeach
                    </div>

                    <div class="totals-section">
                        <div class="total-row">
                            <span class="total-label">Subtotal</span>
                            <span class="total-value">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        @if($invoice->discount_amount > 0)
                        <div class="total-row">
                            <span class="total-label">Discount</span>
                            <span class="total-value">-{{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        @if($invoice->amount_paid > 0)
                        <div class="total-row">
                            <span class="total-label">Paid</span>
                            <span class="total-value">-{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</span>
                        </div>
                        @endif
                        <div class="total-row grand-total">
                            <span class="total-label">Amount Due</span>
                            <span class="total-value">{{ $invoice->currency }} {{ number_format($invoice->balance, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="payment-info">
                    <div class="payment-title">Payment Information</div>
                    <div class="payment-details">
                        <strong>Bank:</strong> National Bank of Malawi<br>
                        <strong>Account Name:</strong> Sukulu Technologies Ltd<br>
                        <strong>Account Number:</strong> 1234567890<br>
                        <strong>Reference:</strong> {{ $invoice->invoice_number }}
                    </div>
                </div>

                @if($invoice->notes)
                <p style="margin-top: 24px; font-size: 14px; color: #64748b;">
                    <strong>Note:</strong> {{ $invoice->notes }}
                </p>
                @endif
            </div>

            <div class="email-footer">
                <p class="footer-text">
                    If you have any questions about this invoice, please contact us at<br>
                    <a href="mailto:support@sukulu.com">support@sukulu.com</a>
                </p>
                <p class="footer-text" style="margin-top: 16px;">
                    &copy; {{ date('Y') }} Sukulu SMIS. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
