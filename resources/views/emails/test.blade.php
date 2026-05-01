<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .btn { 
            padding: 10px 20px; 
            background: #4CAF50; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
        }
    </style>
</head>
<body>
    <h2>🎉 Laravel Mail is Working!</h2>
    <p>If you're seeing this, your mail setup is configured correctly.</p>
    <p><a class="btn" href="#">This is a test button</a></p>
    <p>Sent from: {{ config('app.name') }}</p>
</body>
</html>