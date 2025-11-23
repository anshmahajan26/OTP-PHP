<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
</head>
<body>
    <h2>Verify OTP</h2>

    @if(session('success'))
        <div style="color: green;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->has('otp'))
        <p style="color:red;"> {{ $errors->first('otp') }}</p>
    @endif

    <form action="{{ route('verify.otp') }}" method="POST">
        @csrf
        <div>
            <input type="text" name="otp" placeholder="Enter OTP" required> <br><br>
            <button type="submit">Verify OTP</button>
        </div>
    </form>

    <form action="{{ route('resend.otp') }}" method="POST" style="margin-top: 10px;">
        @csrf
        <button type="submit" style="background-color: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;" onclick="this.disabled=true; this.form.submit();">
            Resend OTP
        </button>
    </form>

    <p style="margin-top: 10px;"><a href="/login">Back to Login</a></p>
</body>
</html>