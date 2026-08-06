<?php
$token = trim((string) ($_GET['token'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | GirffoN</title>
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      background: #f5f1ea;
      color: #1f1812;
      font-family: Georgia, serif;
      padding: 24px;
    }

    .reset-card {
      width: 100%;
      max-width: 460px;
      background: #fffdf9;
      border: 1px solid #e5ddd0;
      border-radius: 18px;
      padding: 28px;
      box-shadow: 0 16px 42px rgba(46, 28, 10, 0.08);
    }

    h1 {
      margin: 0 0 12px;
      font-size: 2rem;
    }

    p {
      margin: 0 0 18px;
      color: #6e5c4a;
      line-height: 1.6;
    }

    label {
      display: block;
      margin: 0 0 8px;
      font-weight: 700;
    }

    input {
      width: 100%;
      box-sizing: border-box;
      padding: 13px 14px;
      border-radius: 12px;
      border: 1px solid #d7c8b6;
      margin-bottom: 16px;
      font: inherit;
    }

    button,
    a {
      font: inherit;
    }

    button {
      width: 100%;
      border: 0;
      border-radius: 999px;
      padding: 13px 18px;
      background: #1f1812;
      color: #f4ebdf;
      font-weight: 700;
      cursor: pointer;
    }

    .status {
      margin-top: 16px;
      min-height: 24px;
      font-weight: 600;
    }

    .status.is-error {
      color: #b43333;
    }

    .status.is-success {
      color: #24573a;
    }

    .back-link {
      display: inline-block;
      margin-top: 18px;
      color: #6e5c4a;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <main class="reset-card">
    <h1>Reset Password</h1>
    <p>Enter your new password below to finish resetting your GirffoN account password.</p>

    <form id="gfResetPasswordForm" novalidate>
      <input type="hidden" id="gfResetToken" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

      <label for="gfResetPassword">New Password</label>
      <input id="gfResetPassword" type="password" autocomplete="new-password" required>

      <label for="gfResetPasswordConfirm">Confirm Password</label>
      <input id="gfResetPasswordConfirm" type="password" autocomplete="new-password" required>

      <button type="submit">Reset Password</button>
    </form>

    <div id="gfResetPasswordStatus" class="status" role="status" aria-live="polite"></div>
    <a class="back-link" href="/GirffoN/Index.html">Back to GirffoN</a>
  </main>

  <script src="JS/analytics.js?v=20260804r14"></script>
  <script>
    (function () {
      const form = document.getElementById('gfResetPasswordForm');
      const tokenField = document.getElementById('gfResetToken');
      const passwordField = document.getElementById('gfResetPassword');
      const confirmField = document.getElementById('gfResetPasswordConfirm');
      const statusNode = document.getElementById('gfResetPasswordStatus');

      function setStatus(message, isError, isSuccess) {
        statusNode.textContent = message || '';
        statusNode.className = 'status' + (isError ? ' is-error' : '') + (isSuccess ? ' is-success' : '');
      }

      form.addEventListener('submit', async function (event) {
        event.preventDefault();
        setStatus('', false, false);

        const token = String(tokenField.value || '').trim();
        const newPassword = String(passwordField.value || '');
        const confirmPassword = String(confirmField.value || '');

        if (!token) {
          setStatus('This reset link is invalid or missing.', true, false);
          return;
        }

        try {
          const response = await fetch('/GirffoN/backend/auth/reset-password.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              token: token,
              new_password: newPassword,
              confirm_password: confirmPassword
            })
          });

          const payload = await response.json();
          if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to reset password.');
          }

          form.reset();
          tokenField.value = token;
          setStatus(payload.message || 'Password reset successfully.', false, true);
        } catch (error) {
          setStatus(error && error.message ? error.message : 'Unable to reset password.', true, false);
        }
      });
    })();
  </script>
</body>
</html>