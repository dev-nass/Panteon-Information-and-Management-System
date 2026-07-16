<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Password Reset Code</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 40px 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0"
                    style="background:#ffffff; border-radius:8px; padding:32px;">
                    <tr>
                        <td style="text-align:center;">
                            <h2 style="color:#166534; margin-bottom:8px;">Panteon De Dasmariñas</h2>
                            <p style="color:#374151; font-size:15px; margin-bottom:24px;">
                                Use the code below to reset your password. This code expires in 15 minutes.
                            </p>
                            <div
                                style="font-size:32px; font-weight:bold; letter-spacing:8px; color:#111827; background:#f3f4f6; padding:16px; border-radius:6px; margin-bottom:24px;">
                                {{ $code }}
                            </div>
                            <p style="color:#6b7280; font-size:13px;">
                                If you didn't request a password reset, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>