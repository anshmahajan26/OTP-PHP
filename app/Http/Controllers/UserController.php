<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Added for debugging

class UserController extends Controller
{
    
    // !Show Register Form
   
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    //! Register User + Send OTP

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed',
        ]);

        // Create User
        //here user is model name and in that model we create user and pass to register function
        $user = User::create([
            'name'   => $request->name,
            'email'  => $request->email,
            'password' => Hash::make($request->password),
            'otp' => rand(100000, 999999),
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // BREVO EMAIL OTP API with detailed error handling
        $brevoApiKey = env('BREVO_API_KEY');
        $senderEmail = env('MAIL_FROM_ADDRESS', 'hello@example.com'); // Make sure this email is verified in Brevo
        $senderName = env('MAIL_FROM_NAME', 'Laravel OTP');

        // Log attempt to send email
        Log::info('Attempting to send OTP email', [
            'user_email' => $user->email,
            'user_name' => $user->name,
            'otp' => $user->otp,
            'sender_email' => $senderEmail
        ]);
        //we are sending this repsonse to brevo email service
        //ye response ka data ham send kr rhe hai post se brevo ko 
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $brevoApiKey,
            'content-type' => 'application/json',
        ])
        ->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => ['name' => $senderName, 'email' => $senderEmail],
            'to' => [[ 'name' => $user->name, 'email' => $user->email ]],
            'subject' => 'Your OTP for Registration',
            //this is email we are sending to user
            'htmlContent' => "<h1>Your OTP Code is</h1> <h2>{$user->otp}</h2><p>This OTP will expire in 10 minutes.</p>",
        ]);
        //this is for error handling. it check that reponse come or not
        Log::info('Brevo API Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        // Check if email was sent successfully
        if ($response->successful()) {
            session(['otp_email' => $user->email]);
            return redirect()
                ->route('verify.otp')
                ->with('success', 'Registration successful. Check your email for the OTP.');
        } else {
            // Log the error for debugging
            Log::error('Failed to send OTP via Brevo', [
                'status' => $response->status(),
                'response' => $response->body(),
                'user_email' => $user->email
            ]);

            // If email fails, delete the user and show an error
            $user->delete(); // Remove user since OTP couldn't be sent
            return back()->withErrors(['email' => 'Failed to send OTP to your email. Error: ' . $response->body()]);
        }
    }


    //! Show Login
  =
    public function showLoginForm()
    {
        return view('auth.login');
    }

   
    //! Login Check + OTP Check
    
    public function login(Request $request)
    {
        //check form input here are right or not
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        //find user by email like it check register page email and if then use
        //Model::where('column', $value)->first(); syntax
        $user = User::where('email',$request->email)->first();


         // NOTE:Hash::check(plain_password, hashed_password_from_db); syntax
        if(!$user || !Hash::check($request->password, $user->password))
        {
            return back()->withErrors(['email' => 'Invalid Credentials']);
        }


        //!if user is not varified then this if loop run
        if(!$user->email_verified_at)
        {
            // Generate a new OTP for login
            $user->otp = rand(100000, 999999);
            $user->otp_expires_at = Carbon::now()->addMinutes(10);
            $user->save();

            // Send OTP to user's email
            $success = $this->sendOtpEmail($user);

            // If sending OTP fails during login, warn the user
            if (!$success) {
                Log::error('Failed to send OTP during login', [
                    'user_email' => $user->email,
                    'otp' => $user->otp
                ]);
            }

            session(['otp_email' => $user->email]);

            return redirect()
                ->route('verify.otp')
                ->withErrors(['otp' => 'Check your email, we sent an OTP. If not received click "Resend OTP".']);
        }

        //?inbuild Auth::login($user);
        // ?This means:
        // ?✔ Logs the user into the application
        // ?✔ Stores user details in the session
        // ?✔ Creates an authenticated session for that user
        // ?✔ The user is now "logged in" until logout or session expires
        Auth::login($user);

        return redirect()->route('home');
    }

   
    // !Show OTP Verify Page
   
    public function showOtpForm()
    {
        return view('auth.verify-otp');
    }

  
    //! Verify OTP
  
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required']);

        $email = session('otp_email');

        $user = User::where('email',$email)->first();

        if(!$user) return back()->withErrors(['otp' => 'User not found']);

        if($user->otp_expires_at < now())
        {
            return back()->withErrors(['otp' => 'OTP expired']);
        }

        if($request->otp != $user->otp)
        {
            return back()->withErrors(['otp' => 'Invalid OTP']);
        }

        $user->email_verified_at = now();
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        Auth::login($user);

        return redirect()->route('home')->with('success','OTP Verified Successfully!');
    }


    // !Send OTP Email (Reusable function)

    public function sendOtpEmail($user)
    {
        $brevoApiKey = env('BREVO_API_KEY');
        $senderEmail = env('MAIL_FROM_ADDRESS', 'hello@example.com');
        $senderName = env('MAIL_FROM_NAME', 'Laravel OTP');

        // Log the attempt
        Log::info('Attempting to send OTP email (sendOtpEmail function)', [
            'user_email' => $user->email,
            'user_name' => $user->name,
            'otp' => $user->otp,
            'sender_email' => $senderEmail
        ]);

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $brevoApiKey,
            'content-type' => 'application/json',
        ])
        ->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => ['name' => $senderName, 'email' => $senderEmail],
            'to' => [[ 'name' => $user->name, 'email' => $user->email ]],
            'subject' => 'Your OTP for Login',
            'htmlContent' => "<h1>Your OTP Code is</h1> <h2>{$user->otp}</h2><p>This OTP will expire in 10 minutes.</p>",
        ]);

        Log::info('OTP Email API Response', [
            'status' => $response->status(),
            'user_email' => $user->email
        ]);

        return $response->successful();
    }

   
    // !Resend OTP
   
    public function resendOtp(Request $request)
    {
        $email = session('otp_email');

        if (!$email) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please login again.']);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'User not found.']);
        }

        // Generate a new OTP
        $user->otp = rand(100000, 999999);
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        // Send the new OTP
        $success = $this->sendOtpEmail($user);

        if ($success) {
            return redirect()->route('verify.otp')->with('success', 'New OTP sent to your email successfully!');
        } else {
            return back()->withErrors(['otp' => 'Failed to send OTP. Please try again later.']);
        }
    }

  
    // !Test Brevo Email Function

    public function testEmail()
    {
        // This is a test function to verify Brevo is working
        $brevoApiKey = env('BREVO_API_KEY');
        $senderEmail = env('MAIL_FROM_ADDRESS', 'hello@example.com');
        $senderName = env('MAIL_FROM_NAME', 'Laravel OTP');

        // Use the email you want to test with
        $recipientEmail = 'nobodyfan11@gmail.com'; // Change this to the email you want to receive the test on
        $recipientName = 'Test User';

        // Log the test attempt
        Log::info('Testing Brevo email configuration', [
            'api_key_exists' => !empty($brevoApiKey),
            'sender_email' => $senderEmail,
            'recipient_email' => $recipientEmail,
            'brevo_key_length' => strlen($brevoApiKey ?? '')
        ]);

        // Check if API key exists
        if (empty($brevoApiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'BREVO_API_KEY is not set in your .env file. Please add your Brevo API key to the .env file.',
                'sender_email' => $senderEmail
            ]);
        }

        // Check if using default unverified email
        if ($senderEmail === 'nobodyfan11@gmail.com') {
            return response()->json([
                'success' => false,
                'message' => 'Default sender email detected. Please update MAIL_FROM_ADDRESS in your .env file with an email that is verified in your Brevo account.',
                'sender_email' => $senderEmail
            ]);
        }

        $testOtp = rand(100000, 999999);

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $brevoApiKey,
            'content-type' => 'application/json',
        ])
        ->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => ['name' => $senderName, 'email' => $senderEmail],
            'to' => [[ 'name' => $recipientName, 'email' => $recipientEmail ]],
            'subject' => 'Test Email from Laravel App',
            'htmlContent' => "<h1>Test OTP: {$testOtp}</h1><p>This is a test email to verify Brevo is working. If you receive this, your setup is correct!</p>",
        ]);

        // Log the response for debugging
        Log::info('Test email API response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully! Check your inbox, spam, and junk folders for the email.',
                'status_code' => $response->status(),
                'sent_to' => $recipientEmail
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Test email failed to send. Check your Brevo configuration. Details logged to laravel.log',
                'status_code' => $response->status(),
                'response' => $response->body(),
                'sender_email' => $senderEmail,
                'recipient_email' => $recipientEmail
            ]);
        }
    }

    // ============================
    // Logout
    // ============================
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    // ============================
    // Home Page (After Login)
    // ============================
    public function home()
    {
        return view('home');
    }

}
