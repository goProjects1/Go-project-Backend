
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Decline Notification</title>
</head>
<body>
<p>Dear User,</p>

<p>Your trip to "{{ $trip->destination }}" has been declined by a guest.</p>

<p><strong>Trip Details:</strong></p>
<ul>
    <li><strong>From:</strong> {{ $trip->pickUp }}</li>
    <li><strong>To:</strong> {{ $trip->destination }}</li>
    <li><strong>Meeting Location:</strong> {{ $trip->meeting_point }}</li>
    <li><strong>Available Seats:</strong> {{ $trip->available_seat }}</li>
    <li><strong>Fee:</strong> {{ $trip->fee_amount }}</li>
    <li><strong>Status:</strong> Accepted</li>
</ul>

<p>If you have any questions or concerns, please contact us at projectgo295@gmail.com.</p>

<p>Best regards,<br>Go-Project Team</p>
</body>
</html>

