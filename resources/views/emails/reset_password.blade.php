<!DOCTYPE html>
<html lang="en">

<head>
    <title>Reset Password</title>
</head>

<body>
    <h3>Hello, {{ $user->name }}</h3>
    <p>
        We have received a request to change your password
        If you requested this, please
        click the link below to change it. If you did not request this, you can ignore this message and your
        password will stay the same.
    </p>
    <a href="{{ env('FRONTEND_URL') }}/auth/reset-password?token={{ $token }}&email={{ $user->email }}">
        {{ env('FRONTEND_URL') }}/auth/reset-password?token={{ $token }}&email={{ $user->email }}
    </a>
    <p>Thank you</p>
</body>

</html>
