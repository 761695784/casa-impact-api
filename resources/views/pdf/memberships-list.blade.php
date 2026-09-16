<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    /*
     * Export PDF "pro" de la liste des adhérents (accord du 2026-09-16),
     * en complément de l'export CSV déjà existant (MembershipController::
     * export()) — même jeu de filtres (search/statut/region/source), mais
     * rendu avec la charte graphique (logo, vert forêt #02542D, ambre
     * #F2A20D, filigrane) plutôt qu'un tableur brut. Format paysage
     * (accord explicite) : plus de colonnes lisibles qu'en portrait pour
     * une liste tabulaire.
     *
     * Header/footer répétés sur chaque page via le classique "position:
     * fixed + décalage négatif dans la marge" de DomPDF (les éléments
     * fixed sont positionnés par rapport à la zone de contenu de la page,
     * un top/bottom négatif les fait donc remonter/descendre dans la
     * marge). La numérotation "Page X / Y" utilise les compteurs CSS
     * counter(page)/counter(pages), supportés par DomPDF.
     */
    @page {
        size: A4 landscape;
        margin: 100pt 24pt 46pt 24pt;
    }

    html, body {
        margin: 0;
        padding: 0;
        font-family: "Helvetica", "DejaVu Sans", sans-serif;
        color: #2b2b26;
    }

    /* ---------- Filigrane (répété sur chaque page) ---------- */
    .watermark {
        position: fixed;
        top: 140pt;
        left: 260pt;
        width: 340pt;
    }

    /* ---------- En-tête (répété sur chaque page) ---------- */
    .page-header {
        position: fixed;
        top: -92pt;
        left: 0pt;
        right: 0pt;
        height: 80pt;
        border-bottom: 2pt solid #02542D;
    }

    .ph-logo {
        position: absolute;
        top: 0pt;
        left: 0pt;
        width: 150pt;
    }

    .ph-title-main {
        position: absolute;
        top: 6pt;
        left: 180pt;
        font-size: 18pt;
        font-weight: bold;
        color: #02542D;
    }

    .ph-title-sub {
        position: absolute;
        top: 28pt;
        left: 180pt;
        font-size: 8.5pt;
        color: #6b6b63;
        font-style: italic;
    }

    .ph-meta {
        position: absolute;
        top: 6pt;
        right: 0pt;
        width: 260pt;
        text-align: right;
        font-size: 8pt;
        color: #4a4a44;
        line-height: 1.5;
    }

    .ph-meta .ph-count {
        color: #F2A20D;
        font-weight: bold;
        font-size: 9.5pt;
    }

    /* ---------- Pied de page (répété sur chaque page) ---------- */
    .page-footer {
        position: fixed;
        bottom: -36pt;
        left: 0pt;
        right: 0pt;
        height: 26pt;
        border-top: 0.75pt solid #d8d8d0;
        font-size: 7.5pt;
        color: #8a8a80;
    }

    .pf-left {
        position: absolute;
        top: 6pt;
        left: 0pt;
    }

    .pf-right {
        position: absolute;
        top: 6pt;
        right: 0pt;
    }

    .pf-right:after {
        content: "Page " counter(page) " / " counter(pages);
    }

    /* ---------- Tableau ---------- */
    table.list {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5pt;
    }

    table.list thead {
        display: table-header-group;
    }

    table.list th {
        background-color: #02542D;
        color: #ffffff;
        text-transform: uppercase;
        font-size: 7.5pt;
        letter-spacing: 0.3pt;
        text-align: left;
        padding: 7pt 6pt;
    }

    table.list td {
        padding: 6pt 6pt;
        border-bottom: 0.5pt solid #e5e5df;
        vertical-align: top;
    }

    table.list tbody tr.odd {
        background-color: #f5f8f5;
    }

    .col-ref { width: 68pt; font-weight: bold; color: #02542D; }
    .col-nom { width: 140pt; font-weight: bold; }
    .col-contact { width: 165pt; }
    .col-contact .tel { color: #6b6b63; font-size: 8pt; }
    .col-territoire { width: 95pt; }
    .col-territoire .dept { color: #6b6b63; font-size: 8pt; }
    .col-engagement { width: 130pt; }
    .col-engagement .domaine { color: #6b6b63; font-size: 8pt; }
    .col-statut { width: 78pt; }
    .col-date { width: 60pt; }

    .pill {
        display: inline-block;
        padding: 2.5pt 8pt;
        border-radius: 8pt;
        font-size: 7.5pt;
        font-weight: bold;
    }

    .pill-validee { background-color: #e3f5ea; color: #036b3a; }
    .pill-en_attente_paiement { background-color: #fdf1dc; color: #92600b; }
    .pill-refusee { background-color: #fde3e8; color: #9f1239; }

    .empty-state {
        text-align: center;
        padding: 40pt 0;
        color: #8a8a80;
        font-size: 10pt;
    }
</style>
</head>
<body>

    @if (file_exists(public_path('images/mail/filigrane-couleur.png')))
        <img class="watermark" src="{{ public_path('images/mail/filigrane-couleur.png') }}">
    @endif

    <div class="page-header">
        @if (file_exists(public_path('images/mail/logo-couleur.png')))
            <img class="ph-logo" src="{{ public_path('images/mail/logo-couleur.png') }}">
        @endif
        <div class="ph-title-main">Liste des Adhérents</div>
        <div class="ph-title-sub">Casa Impact &mdash; trois régions, une vision, un impact</div>
        <div class="ph-meta">
            <div>Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</div>
            <div class="ph-count">{{ $memberships->count() }} adhérent(s)</div>
            @if ($filterSummary)
                <div>{{ $filterSummary }}</div>
            @endif
        </div>
    </div>

    <div class="page-footer">
        <div class="pf-left">Document confidentiel &mdash; données personnelles, usage interne uniquement.</div>
        <div class="pf-right"></div>
    </div>

    @if ($memberships->isEmpty())
        <div class="empty-state">Aucun adhérent ne correspond aux filtres sélectionnés.</div>
    @else
        <table class="list">
            <thead>
                <tr>
                    <th class="col-ref">Référence</th>
                    <th class="col-nom">Nom complet</th>
                    <th class="col-contact">Contact</th>
                    <th class="col-territoire">Territoire</th>
                    <th class="col-engagement">Engagement</th>
                    <th class="col-statut">Statut</th>
                    <th class="col-date">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($memberships as $i => $m)
                    <tr class="{{ $i % 2 === 1 ? 'odd' : '' }}">
                        <td class="col-ref">{{ $m->numero_membre }}</td>
                        <td class="col-nom">{{ $m->nom_complet }}</td>
                        <td class="col-contact">
                            {{ $m->email }}
                            @if ($m->telephone)
                                <br><span class="tel">{{ $m->telephone }}</span>
                            @endif
                        </td>
                        <td class="col-territoire">
                            {{ $regionLabels[$m->region?->value] ?? '—' }}
                            @if ($m->departement)
                                <br><span class="dept">{{ $m->departement }}</span>
                            @endif
                        </td>
                        <td class="col-engagement">
                            {{ $contributionTypeLabels[$m->type_contribution?->value] ?? '—' }}
                            @if ($m->domaine_contribution)
                                <br><span class="domaine">{{ $contributionDomainLabels[$m->domaine_contribution->value] ?? '' }}</span>
                            @endif
                        </td>
                        <td class="col-statut">
                            <span class="pill pill-{{ $m->statut->value }}">{{ $statusLabels[$m->statut->value] ?? $m->statut->value }}</span>
                        </td>
                        <td class="col-date">{{ ($m->validated_at ?? $m->created_at)?->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>
