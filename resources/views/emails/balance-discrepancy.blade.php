<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Discrepancy Alert</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #dc2626;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #e5e7eb;
        }
        th {
            background-color: #f3f4f6;
            font-weight: 600;
        }
        .alert {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 12px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚠️ Balance Discrepancy Alert</h1>
    </div>

    <div class="content">
        <p>Dear Admin,</p>

        <div class="alert">
            <strong>{{ $flaggedUsers->count() }}</strong> user account(s) have been flagged due to balance discrepancies during the automated verification process.
        </div>

        <p>The following users have been identified with balance mismatches:</p>

        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Current Balance</th>
                    <th>Flagged At</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @foreach($flaggedUsers as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>${{ number_format($user->balance, 2) }}</td>
                    <td>{{ $user->flagged_at->format('Y-m-d H:i:s') }}</td>
                    <td style="font-size: 12px;">{{ $user->flagged_reason }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="alert" style="margin-top: 20px;">
            <strong>Action Required:</strong> These users have been restricted from making transactions until their accounts are reviewed and corrected.
        </div>

        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Review each flagged user's transaction history</li>
            <li>Investigate the cause of the discrepancy</li>
            <li>Correct the balance if necessary</li>
            <li>Unflag the user account to restore transaction capabilities</li>
        </ul>

        <div class="footer">
            <p>This is an automated message from the Mini Wallet Balance Verification System.</p>
            <p>Verification runs every 12 hours to ensure data integrity.</p>
            <p>Report generated at: {{ now()->format('Y-m-d H:i:s T') }}</p>
        </div>
    </div>
</body>
</html>

