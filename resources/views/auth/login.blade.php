<h2>login page</h2>
<form action="/login" method="POST">
    @csrf
    <div>
        <input type="text" name="email" placeholder="email"><br><br>
        <input type="text" name="password" placeholder="password"><br><br>

        <button type="submit">Login</button>
    </div>
</form>