<h2>Register form</h2>
<form action="/register" method=POST>
    @csrf
    <div>
        <input type="text" name="name" placeholder="name"><br><br>
         <input type="email" name="email" placeholder="email"><br><br>
         <input type="password" name="password" placeholder="password"><br><br>
         <input type="password" name="password_confirmation" placeholder="password_confirmation"><br><br>
        <button>Register</button>
    </div>
</form>