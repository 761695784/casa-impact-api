<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    /*
     * Réplique du gabarit ModeleCarteMembre.pdf fourni le 2026-08-24.
     * DomPDF (moteur CSS 2.1 limité) : pas de flexbox fiable, on utilise
     * donc un positionnement absolu sur une page à taille fixe (voir
     * MembershipCardService::generate(), setPaper([0,0,680,383])) — la
     * carte a une taille et une mise en page connues à l'avance, ce qui
     * rend le positionnement absolu à la fois simple et fidèle.
     */
    @page {
        margin: 0;
    }

    html, body {
        margin: 0;
        padding: 0;
        width: 680px;
        height: 383px;
        font-family: "Helvetica", "DejaVu Sans", sans-serif;
        background-color: #f7f6f2;
    }

    .card {
        position: relative;
        width: 680px;
        height: 383px;
        overflow: hidden;
    }

    /* Filigrane baobab très pâle, à droite — voir filigrane-couleur.png,
       déjà à ~11% d'opacité dans le fichier source lui-même, donc pas
       besoin d'opacity CSS supplémentaire (rendu opacity peu fiable sur
       les images dans certaines versions de DomPDF). */
    .watermark {
        position: absolute;
        top: 30px;
        right: 40px;
        width: 340px;
    }

    /* Bandeau vert foncé à gauche, texte "Carte de membre" en rotation. */
    .sidebar {
        position: absolute;
        top: 0;
        left: 0;
        width: 90px;
        height: 383px;
        background-color: #163a2b;
    }

    .sidebar-text {
        position: absolute;
        top: 155px;
        left: -110px;
        width: 320px;
        text-align: center;
        transform: rotate(-90deg);
        color: #ffffff;
        font-size: 34px;
        font-weight: bold;
    }

    .logo {
        position: absolute;
        top: 28px;
        left: 118px;
        width: 190px;
    }

    .id-pill {
        position: absolute;
        top: 34px;
        right: 36px;
        background-color: #163a2b;
        color: #ffffff;
        font-weight: bold;
        font-size: 20px;
        padding: 10px 26px;
        border-radius: 22px;
    }

    .photo-box {
        position: absolute;
        top: 118px;
        left: 118px;
        width: 148px;
        height: 182px;
        border: 3px solid #d3992e;
        background-color: #ffffff;
        text-align: center;
    }

    .photo-box img {
        width: 148px;
        height: 182px;
        object-fit: cover;
    }

    .fields {
        position: absolute;
        top: 128px;
        left: 300px;
        width: 340px;
    }

    .field-nom {
        color: #163a2b;
        font-size: 30px;
        font-weight: bold;
        margin-bottom: 26px;
    }

    .field-statut {
        color: #d3992e;
        font-size: 19px;
        font-weight: bold;
        margin-bottom: 2px;
    }

    .field-region {
        color: #d3992e;
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 18px;
    }

    .field-date-label {
        color: #6b6b63;
        font-size: 12px;
    }

    .field-date {
        color: #3c3c36;
        font-size: 16px;
        font-weight: bold;
    }

    .footer {
        position: absolute;
        bottom: 22px;
        right: 40px;
        color: #163a2b;
        font-size: 14px;
        font-weight: bold;
        font-style: italic;
    }
</style>
</head>
<body>
    <div class="card">
        @if (file_exists(public_path('images/mail/filigrane-couleur.png')))
            <img class="watermark" src="{{ public_path('images/mail/filigrane-couleur.png') }}">
        @endif

        <div class="sidebar">
            <div class="sidebar-text">Carte de membre</div>
        </div>

        @if (file_exists(public_path('images/mail/logo-couleur.png')))
            <img class="logo" src="{{ public_path('images/mail/logo-couleur.png') }}">
        @endif

        <div class="id-pill">{{ $id }}</div>

        <div class="photo-box">
            @if ($photoAbsolutePath && file_exists($photoAbsolutePath))
                <img src="{{ $photoAbsolutePath }}">
            @endif
        </div>

        <div class="fields">
            <div class="field-nom">{{ $nom }}</div>
            <div class="field-statut">{{ $statut }}</div>
            <div class="field-region">{{ $region }}</div>
            <div class="field-date-label">Depuis</div>
            <div class="field-date">{{ $date }}</div>
        </div>

        <div class="footer">Casa Impact, trois régions - une vision - un impact.</div>
    </div>
</body>
</html>
