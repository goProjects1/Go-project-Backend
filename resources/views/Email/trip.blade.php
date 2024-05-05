<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Invitation</title>
</head>
<body>
<p>Dear user,</p>

<p>You have been invited to join a trip. Please review the details below and take action:</p>

<p><strong>Trip Details:</strong></p>
<ul>
    <li><strong>Driver_Name:</strong> {{ $name }}</li>
    <li><strong>From:</strong> {{ $trip->pickUp }}</li>
    <li><strong>To:</strong> {{ $trip->destination }}</li>
    <li><strong>Meeting Location:</strong> {{ $trip->meeting_point }}</li>
    <li><strong>Charges:</strong> {{ $trip->charge }}</li>
    <li><strong>Available Seats:</strong> {{ $trip->available_seat }}</li>
    <li><strong>Fee:</strong> £{{ $trip->fee_amount }}</li>
    <li><strong>Property Type:</strong> {{ $type }}</li>
    <li><strong>Property Model:</strong> {{ $model }}</li>
    <li><strong>Property Registration Number:</strong> {{ $registrationNo }}</li>
    <li><strong>Time Created:</strong> {{ now() }}</li>
</ul>

<p>
    <a href="{{ $inviteLink }}&action=accept" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Accept Trip</a>
    <a href="{{ $inviteLink }}&action=decline" style="background-color: #f44336; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Decline Trip</a>
</p>
<br>
<p><strong>If this trip requires a fee, you can negotiate by logging into your account and messaging the driver directly.</strong></p>
<p>If you have any questions or concerns, please contact us at projectgo295@gmail.com</p>

<p>Best regards,<br>Go-Project</p>
</body>
</html>
