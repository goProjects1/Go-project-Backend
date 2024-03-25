<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Feedback</title>
</head>
<body>
<h2>User Feedback</h2>
<p>Dear Admin,</p>

<p>You have received a new feedback:</p>
<p><strong>User Comment:</strong> {{ $feedback->description }}</p>
<p><strong>Severity:</strong> {{ $feedback->severity }}</p>
<p><strong>Rating:</strong> {{ $feedback->rating }}/5</p>
<p>Thank you for your attention.</p>
</body>
</html>
