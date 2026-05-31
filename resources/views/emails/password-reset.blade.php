<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $mailLocale ?? app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('messages.password_reset_subject', ['app' => $appName], $mailLocale ?? app()->getLocale()) }}</title>
</head>
<body style="margin: 0; padding: 0; background: #f4f7fb; font-family: Arial, Helvetica, sans-serif; color: #172033;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f4f7fb; padding: 32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 620px; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5ebf3;">
                    <tr>
                        <td style="background: #0f172a; padding: 28px 32px;">
                            <div style="font-size: 26px; font-weight: 700; letter-spacing: .3px; color: #ffffff;">Management App</div>
                            <div style="margin-top: 8px; font-size: 14px; color: #cbd5e1;">ERP Management Portal</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 34px 32px 12px;">
                            <h1 style="margin: 0 0 18px; font-size: 24px; line-height: 1.3; color: #0f172a;">
                                {{ trans('messages.password_reset_heading', [], $mailLocale ?? app()->getLocale()) }}
                            </h1>

                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.7;">
                                {{ trans('messages.hello', [], $mailLocale ?? app()->getLocale()) }} {{ $user->name }},
                            </p>

                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.7;">
                                {{ trans('messages.password_reset_intro_brand', ['app' => $appName], $mailLocale ?? app()->getLocale()) }}
                            </p>

                            <p style="margin: 0 0 26px; font-size: 15px; line-height: 1.7;">
                                {{ trans('messages.password_reset_instruction', [], $mailLocale ?? app()->getLocale()) }}
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="border-radius: 8px; background: #2563eb;">
                                        <a href="{{ $resetUrl }}" style="display: inline-block; padding: 14px 24px; color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none;">
                                            {{ trans('messages.password_reset_action', [], $mailLocale ?? app()->getLocale()) }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 26px 0 0; font-size: 14px; line-height: 1.7; color: #475569;">
                                {{ trans('messages.password_reset_expire', ['minutes' => $expireMinutes], $mailLocale ?? app()->getLocale()) }}
                            </p>

                            <p style="margin: 12px 0 0; font-size: 14px; line-height: 1.7; color: #475569;">
                                {{ trans('messages.password_reset_no_action', [], $mailLocale ?? app()->getLocale()) }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 32px 34px;">
                            <div style="height: 1px; background: #e5ebf3; margin-bottom: 20px;"></div>
                            <p style="margin: 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                                {{ trans('messages.regards', [], $mailLocale ?? app()->getLocale()) }},<br>
                                <strong style="color: #0f172a;">L'equipe {{ $appName }}</strong>
                            </p>
                            <p style="margin: 18px 0 0; font-size: 12px; line-height: 1.6; color: #94a3b8;">
                                {{ trans('messages.password_reset_fallback', [], $mailLocale ?? app()->getLocale()) }}<br>
                                <a href="{{ $resetUrl }}" style="color: #2563eb; word-break: break-all;">{{ $resetUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
