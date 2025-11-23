<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
</head>
<body>
    <h1>Welcome, {{auth()->user()->name}}!</h1>
    <p>You are successfully logged in.</p>

    <div>
        <a href="/logout">Logout</a>
    </div>
</body>
</html>