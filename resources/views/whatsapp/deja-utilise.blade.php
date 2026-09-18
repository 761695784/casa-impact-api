<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lien déjà utilisé — Casa Impact</title>
<style>
    body {
        margin: 0;
        padding: 24px;
        min-height: 100vh;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f7f6f2;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: #2b2b26;
    }
    .card {
        max-width: 440px;
        width: 100%;
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 10px 30px rgba(2, 84, 45, 0.08);
        padding: 40px 32px;
        text-align: center;
    }
    .card img { height: 48px; margin-bottom: 20px; }
    .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #fdf1dc;
        color: #92600b;
        font-size: 28px;
        margin-bottom: 18px;
    }
    h1 { font-size: 19px; color: #02542D; margin: 0 0 12px; }
    p { font-size: 14px; line-height: 1.6; color: #55554d; margin: 0 0 8px; }
    .footer { margin-top: 24px; font-size: 12px; color: #9a9a90; }
    a { color: #02542D; }
</style>
</head>
<body>
    <div class="card">
        <img src="{{ asset('images/mail/logo-couleur.png') }}" alt="Casa Impact">
        <div class="badge">&#9888;</div>
        <h1>Ce lien a déjà été utilisé</h1>
        <p>Ce lien d'invitation au groupe WhatsApp de Casa Impact est personnel et à usage unique — il a déjà servi une première fois et ne peut plus être réutilisé, y compris par la personne à qui il était destiné.</p>
        <p>Si vous êtes bien l'adhérent concerné et que vous n'avez pas encore rejoint le groupe, contactez-nous directement pour obtenir un nouveau lien.</p>
        <div class="footer">
            Casa Impact — Trois régions, une vision, un impact<br>
            <a href="mailto:casaimpactF0rt@gmail.com">casaimpactF0rt@gmail.com</a>
        </div>
    </div>
</body>
</html>
