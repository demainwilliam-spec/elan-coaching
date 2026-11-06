<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
{{-- Mise en page des mails de réservation. Les messageries ignorent le CSS externe et gèrent mal flex/grid :
     styles en ligne et <table> pour la mise en page, la seule méthode fiable partout (Outlook compris) --}}
<body style="margin: 0; padding: 0; background-color: #F2F2F2; font-family: Arial, sans-serif; color: #222; line-height: 1.6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F2F2F2;">
        <tr>
            <td align="center" style="padding: 24px 12px;">
                {{-- max-width : 600px sur ordinateur, toute la largeur sur téléphone --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #FFFFFF;">
                    <tr>
                        <td style="background-color: #0A0A0A; padding: 20px 32px;">
                            {{-- Pinyon Script ne se charge pas dans les messageries : Georgia italique en remplacement --}}
                            <span style="font-family: Georgia, serif; font-style: italic; font-size: 26px; color: #FFFFFF;">JF Coaching</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 16px 32px; border-top: 1px solid #E5E5E5; font-size: 12px; color: #777777;">
                            JF Coaching — Coaching sportif, Belgique
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
