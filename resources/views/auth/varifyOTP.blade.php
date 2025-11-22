<h2>Varify OTP</h2>
<form action="/otpverify" method="POST">
    @csrf 
    <input type="text" name="otp" placeholder="enter otp"> <br><br>
    <button type="submit">Submit</button>
</form>

<!-- @if($errors->has('otp'))
<p style="color:red;"> {{$errors->first('otp')}}</p>
@endif -->