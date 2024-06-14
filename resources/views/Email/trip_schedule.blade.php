<!DOCTYPE html>
<html>
<head>
    <title>Trip Schedule Accepted</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333333;
        }
        p {
            font-size: 16px;
            color: #555555;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            background-color: #f9f9f9;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        ul li:nth-child(odd) {
            background-color: #e9e9e9;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777777;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Trip Schedule Accepted</h1>
    <p>Hello {{ $user->email }},</p>
    <p>Your trip schedule has been accepted.</p>
    <p>Details:</p>
    <ul>
        <li><strong>Meeting Point:</strong> {{ $tripSchedule->meeting_point }}</li>
        <li><strong>Destination:</strong> {{ $tripSchedule->destination }}</li>
    </ul>
    <div class="footer">
        <p>&copy; {{ date('Y') }} <a href="www.pathpalz.com">PathPalz</a> . All rights reserved.</p>
    </div>
</div>
</body>
</html>

