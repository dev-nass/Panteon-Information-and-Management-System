<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Clerk Invitation</title>
</head>

<body style="font-family: 'Bona Nova', 'Georgia', serif; background-color: #f4f4f4; padding: 40px 0; margin: 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#166534; padding:32px 24px; text-align:center;">
                            <h1 style="color:#facc15; font-size:32px; font-weight:100; margin:0; letter-spacing:1px;">
                                Panteon De<br>Dasmariñas
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="color:#111827; font-size:22px; font-weight:bold; margin:0 0 8px;">
                                Clerk Invitation
                            </h2>
                            <p style="color:#374151; font-size:15px; line-height:1.6; margin:0 0 16px;">
                                You have been invited to register as a clerk.
                            </p>
                            <p style="color:#6b7280; font-size:14px; line-height:1.6; margin:0 0 24px;">
                                Click the button below to complete your registration. This link expires in 24 hours.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $registrationUrl }}"
                                            style="display:inline-block; background:#16a34a; color:#ffffff; text-decoration:none; font-size:15px; font-weight:600; padding:12px 32px; border-radius:6px;">
                                            Complete Registration
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="color:#9ca3af; font-size:12px; margin:24px 0 0; word-break:break-all;">
                                Or copy this link: <a href="{{ $registrationUrl }}" style="color:#16a34a;">{{ $registrationUrl }}</a>
                            </p>
                            <p style="color:#6b7280; font-size:13px; margin:24px 0 0; border-top:1px solid #f3f4f6; padding-top:16px;">
                                If you did not expect this invitation, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>