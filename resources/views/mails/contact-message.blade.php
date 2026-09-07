<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>New Contact Inquiry</title>
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
                            <p style="color:#bbf7d0; font-size:13px; margin:8px 0 0; letter-spacing:0.5px;">
                                New Contact Inquiry
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="color:#111827; font-size:18px; font-weight:bold; margin:0 0 16px;">
                                You have a new message from your website
                            </h2>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px; border:1px solid #f3f4f6; border-radius:6px; overflow:hidden;">
                                <tr>
                                    <td style="padding:12px 16px; background:#f9fafb; font-size:13px; color:#6b7280; width:120px; border-bottom:1px solid #f3f4f6;">
                                        Name
                                    </td>
                                    <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:600; border-bottom:1px solid #f3f4f6;">
                                        {{ $firstName }} {{ $lastName }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 16px; background:#f9fafb; font-size:13px; color:#6b7280; width:120px; border-bottom:1px solid #f3f4f6;">
                                        Email
                                    </td>
                                    <td style="padding:12px 16px; font-size:14px; border-bottom:1px solid #f3f4f6;">
                                        <a href="mailto:{{ $visitorEmail }}" style="color:#16a34a; text-decoration:none;">{{ $visitorEmail }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 16px; background:#f9fafb; font-size:13px; color:#6b7280; width:120px;">
                                        Phone
                                    </td>
                                    <td style="padding:12px 16px; font-size:14px; color:#111827;">
                                        @if ($phoneNumber)
                                            <a href="tel:{{ $phoneNumber }}" style="color:#16a34a; text-decoration:none;">{{ $phoneNumber }}</a>
                                        @else
                                            <span style="color:#9ca3af; font-style:italic;">Not provided</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#374151; font-size:13px; font-weight:600; margin:0 0 8px; text-transform:uppercase; letter-spacing:0.5px;">
                                Message
                            </p>
                            <div style="background:#f9fafb; border:1px solid #e5e7eb; border-left:4px solid #16a34a; border-radius:6px; padding:16px; margin-bottom:24px;">
                                <p style="color:#1f2937; font-size:14px; line-height:1.7; margin:0; white-space:pre-wrap; word-break:break-word;">{{ $visitorMessage }}</p>
                            </div>

                            <p style="color:#9ca3af; font-size:12px; margin:0; text-align:center; border-top:1px solid #f3f4f6; padding-top:16px;">
                                Reply directly to this email to respond to {{ $firstName }}.<br>
                                Received on {{ now()->setTimezone('Asia/Manila')->format('F j, Y \a\t g:i A') }} (Asia/Manila)
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="color:#9ca3af; font-size:11px; margin:16px 0 0; text-align:center;">
                    This message was sent via the Contact Us form on Panteon De Dasmariñas website.
                </p>
            </td>
        </tr>
    </table>
</body>

</html>
