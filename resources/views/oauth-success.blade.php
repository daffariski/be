<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>OAuth Success</title>
  <script>
    window.opener.postMessage({
      success: 1,
      token: "{{ $token }}",
      user_id: "{{ $user_id }}",
    }, "*");
    window.close();
  </script>
</head>
<body>
  <p>Login successful! You can close this window.</p>
</body>
</html>
