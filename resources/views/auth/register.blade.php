<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>

    @if($errors->any())
        <div style="color: red;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="/register" method="POST">
        @csrf
        <div>
            <input type="text" name="name" placeholder="Full Name" required><br><br>
            <input type="email" name="email" placeholder="Email" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>
            <input type="password" name="password_confirmation" placeholder="Confirm Password" required><br><br>
            <button type="submit">Register</button>
        </div>
    </form>

    <p><a href="/login">Already have an account? Login here</a></p>
</body>
</html>