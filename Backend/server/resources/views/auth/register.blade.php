<!-- resources/views/auth/register.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <form action="{{ route('register') }}" method="POST">
    @csrf
    <!-- Your form fields here -->
    <input type="text" name="id_user" placeholder="User ID">
    <input type="email" name="email" placeholder="Email">
    <input type="password" name="password" placeholder="Password">
    <input type="password" name="password_confirmation" placeholder="Confirm Password">
    <input type="text" name="discriminator" placeholder="Discriminator">
    <button type="submit">Register</button>
</form>

</body>
</html>
