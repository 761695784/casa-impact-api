# Accéder à la documentation de l'API Casa Impact

## C'est quoi ?

Le backend utilise un outil appelé **Scramble**, qui génère automatiquement une interface de documentation interactive à partir du code de l'API — comme une "carte" de tous les points d'accès (endpoints) disponibles : candidatures, adhésions, actualités, talents, partenaires, etc. Chaque endpoint y est listé avec ce qu'il attend en entrée et ce qu'il renvoie en sortie, et on peut tester une requête directement depuis l'interface, sans outil externe.

Rien à installer ni à relancer : c'est une page qui se met à jour toute seule à chaque fois que le code de l'API change.

## Où la trouver

| Environnement | Adresse |
|---|---|
| En local (sur ta machine, `php artisan serve`) | `http://localhost:8000/docs/api` |
| Une fois le site en ligne | `https://api.casaimpact.org/docs/api` |

## Qui peut y accéder

- **En local** : la page est ouverte sans restriction, tu peux y aller directement.
- **Une fois en ligne** : l'accès est réservé à l'équipe — il faut être connecté avec un compte ayant l'un de ces trois rôles : `administrateur-principal`, `communication`, ou `gestionnaire-candidatures`. Un visiteur du site public ne peut pas y accéder.

**Comment se connecter pour la consulter en production** : connecte-toi d'abord normalement sur l'espace admin (`https://casaimpact.org/admin` ou l'URL de connexion habituelle) dans le même navigateur, puis ouvre `https://api.casaimpact.org/docs/api` dans un nouvel onglet — la page reconnaît automatiquement ta session.

## Comment l'utiliser

- Les endpoints sont regroupés en deux grandes sections : **Admin** (`/api/admin/...`, ceux qui nécessitent d'être connecté) et **Public** (`/api/public/...`, ouverts à tous — ce sont ceux qu'utilise le site public).
- Un bouton **"Try it"** permet de tester une requête directement depuis la page (remplir les champs, envoyer, voir la réponse). Pour les endpoints Admin, ça ne fonctionne que si tu es connecté dans ce même navigateur, comme expliqué ci-dessus.
- L'authentification admin fonctionne par cookie de session (pas de "jeton" à copier-coller) — c'est géré automatiquement par le navigateur une fois connecté.

## Exporter la documentation (pour Postman, Insomnia, ou un développeur externe)

Si un jour tu (ou quelqu'un d'autre) as besoin du fichier brut de la documentation — par exemple pour l'importer dans un outil comme Postman — la commande suivante, lancée dans le dossier du backend, génère un fichier `api.json` :

```
php artisan scramble:export
```

## À savoir

La structure technique de chaque endpoint (ce qu'il attend, ce qu'il renvoie) s'affiche déjà correctement pour la quasi-totalité de l'API. En revanche, environ un tiers des endpoints ont aujourd'hui une vraie description écrite en français expliquant à quoi ils servent — les autres s'affichent avec un libellé plus générique, déduit automatiquement du code. Ça reste utilisable tel quel ; si tu veux à un moment une documentation avec une explication rédigée pour chaque endpoint, dis-le moi et je m'en occuperai (c'est un travail plus long, sur une trentaine de fichiers).
