<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: #f8fafc;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%"
        style="background-color: #f8fafc; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 50px 15px;">

                <!-- Main Card Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="600"
                    style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">

                    <!-- Header Branding -->
                    <tr>
                        <td align="center" style="background-color: #0f172a; padding: 35px 20px;">
                            <span style="color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.5px;">
                                Laravel Admin Dashboard
                            </span>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 45px 40px 35px 40px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td
                                        style="color: #0f172a; font-size: 22px; font-weight: 700; padding-bottom: 16px; letter-spacing: -0.5px;">
                                        Reset Your Password 🔐
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        style="color: #475569; font-size: 16px; line-height: 26px; padding-bottom: 10px;">
                                        Hi <strong>{{ $name }}</strong>,
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        style="color: #475569; font-size: 16px; line-height: 26px; padding-bottom: 30px;">
                                        It happens to the best of us! Click the secure button below to choose a new
                                        password for your account.
                                    </td>
                                </tr>

                                <!-- Action Button -->
                                <tr>
                                    <td align="left" style="padding-bottom: 35px;">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" bgcolor="#2563eb" style="border-radius: 8px;">
                                                    <a href="{{ $resetlink }}" target="_blank"
                                                        style="font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; display: inline-block; background-color: #2563eb;">
                                                        Reset Password &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Fallback Clean Link Option -->
                                <tr>
                                    <td
                                        style="color: #64748b; font-size: 14px; line-height: 22px; padding-bottom: 6px;">
                                        If the button doesn't work, you can click the link below:
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 30px;">
                                        <a href="{{ $resetlink }}" target="_blank"
                                            style="color: #2563eb; font-size: 14px; font-weight: 600; text-decoration: underline;">
                                            Reset Your Password Securely
                                        </a>
                                    </td>
                                </tr>

                                <!-- Security Notice -->
                                <tr>
                                    <td
                                        style="color: #94a3b8; font-size: 13px; line-height: 20px; border-top: 1px solid #f1f5f9; padding-top: 25px;">
                                        If you didn't request a password reset, you can safely ignore this email. Your
                                        password will remain unchanged.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer Section -->
                    <tr>
                        <td align="center"
                            style="background-color: #f8fafc; padding: 24px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #94a3b8; font-size: 13px; margin: 0; line-height: 18px;">
                                &copy; <?= date('Y') ?> Admin Panel Setup. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>

</html>
