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
        width: 680pt;
        height: 383pt;
        font-family: "Helvetica", "DejaVu Sans", sans-serif;
        background-color: #f7f6f2;
    }

    .card {
        position: relative;
        width: 680pt;
        height: 383pt;
        overflow: hidden;
    }

    /* Filigrane baobab très pâle, à droite — voir filigrane-couleur.png,
       déjà à ~11% d'opacité dans le fichier source lui-même, donc pas
       besoin d'opacity CSS supplémentaire (rendu opacity peu fiable sur
       les images dans certaines versions de DomPDF). */
    .watermark {
        position: absolute;
        top: 30pt;
        right: 40pt;
        width: 340pt;
    }

    /* Bandeau vert foncé à gauche, texte "Carte de membre" en rotation. */
    .sidebar {
        position: absolute;
        top: 0;
        left: 0;
        width: 90pt;
        height: 383pt;
        background-color: #02542D;
    }

    .sidebar-text {
        position: absolute;
        top: 155pt;
        left: -110pt;
        width: 320pt;
        text-align: center;
        transform: rotate(-90deg);
        color: #ffffff;
        font-size: 34pt;
        font-weight: bold;
    }

    .logo {
        position: absolute;
        top: 28pt;
        left: 118pt;
        width: 190pt;
    }

    .id-pill {
        position: absolute;
        top: 34pt;
        right: 36pt;
        background-color: #02542D;
        color: #ffffff;
        font-weight: bold;
        font-size: 20pt;
        padding: 10pt 26pt;
        border-radius: 22pt;
    }

    .photo-box {
        position: absolute;
        top: 150pt;
        left: 118pt;
        width: 148pt;
        height: 182pt;
        border: 3pt solid #F2A20D;
        background-color: #ffffff;
        text-align: center;
    }

    .photo-box img {
        width: 148pt;
        height: 182pt;
        object-fit: cover;
    }

    .fields {
        position: absolute;
        top: 160pt;
        left: 300pt;
        width: 340pt;
    }

    .field-nom {
        color: #02542D;
        font-size: 30pt;
        font-weight: bold;
        margin-bottom: 26pt;
    }

    .field-statut {
        color: #F2A20D;
        font-size: 19pt;
        font-weight: bold;
        margin-bottom: 2pt;
    }

    .field-region {
        color: #F2A20D;
        font-size: 32pt;
        font-weight: bold;
        margin-bottom: 18pt;
    }

    .field-date-label {
        color: #6b6b63;
        font-size: 12pt;
    }

    .field-date {
        color: #3c3c36;
        font-size: 16pt;
        font-weight: bold;
    }

    .footer {
        position: absolute;
        bottom: 22pt;
        right: 40pt;
        color: #02542D;
        font-size: 14pt;
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
