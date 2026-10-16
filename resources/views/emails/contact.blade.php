<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
</head>
{{-- Les messageries ignorent les feuilles de style externes : les styles d'un email s'écrivent en ligne --}}
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.6;">
    <h2 style="margin: 0 0 1rem;">Nouveau message depuis le formulaire de contact</h2>

    <p><strong>Nom :</strong> {{ $name }}</p>
    <p><strong>Email :</strong> {{ $email }}</p>

    <p><strong>Message :</strong></p>
    {{-- e() échappe le texte du visiteur, nl2br() convertit ensuite les retours à la ligne en <br>,
         et {!! !!} affiche le résultat sans le ré-échapper. Jamais de {!! !!} sans e() sur des données visiteur. --}}
    <p>{!! nl2br(e($messageText)) !!}</p>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 2rem 0 1rem;">
    <p style="font-size: 0.85rem; color: #777;">Pour répondre, utilisez simplement « Répondre » : le message partira vers {{ $email }}.</p>
</body>
</html>
