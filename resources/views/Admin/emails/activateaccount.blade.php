<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activation - Laravel Admin Dashboard</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
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
    
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 50px 15px;">
                
                <!-- Main Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #0f172a; padding: 40px 20px;">
                            <h1 style="color: #ffffff; font-size: 22px; font-weight: 700; margin: 0; letter-spacing: -0.5px;">
                                Laravel Admin Dashboard
                            </h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 45px 40px 30px 40px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="color: #0f172a; font-size: 22px; font-weight: 700; padding-bottom: 16px; letter-spacing: -0.5px;">
                                        Verify your email address
                                    </td>
                                </tr>
                                <tr>
                                    <td style="color: #475569; font-size: 16px; line-height: 26px; padding-bottom: 30px;">
                                        Thanks for creating an account with us! We're excited to have you on board. Please click the secure button below to activate your admin panel access.
                                    </td>
                                </tr>
                                
                                <!-- Button -->
                                <tr>
                                    <td align="left" style="padding-bottom: 35px;">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" bgcolor="#2563eb" style="border-radius: 8px;">
                                                    <a href="{{ $activationLink }}" target="_blank" style="font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; display: inline-block; background-color: #2563eb;">
                                                        Activate Account &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Fallback Clean Link -->
                                <tr>
                                    <td style="color: #64748b; font-size: 14px; line-height: 22px; padding-bottom: 6px;">
                                        If the button doesn't work, you can click the link below:
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 25px;">
                                        <a href="{{ $activationLink }}" target="_blank" style="color: #2563eb; font-size: 14px; font-weight: 600; text-decoration: underline;">
                                            Verify Your Account Securely &rarr;
                                        </a>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="color: #94a3b8; font-size: 13px; line-height: 20px; border-top: 1px solid #f1f5f9; padding-top: 30px; margin-top: 30px;">
                                        If you didn't request this registration, you can safely ignore this email.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 24px; border-top: 1px solid #e2e8f0;">
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