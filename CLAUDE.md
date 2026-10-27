# Projet : JF Coaching — site vitrine coaching sportif

## Historique
- 11/10/2026 : début du travail avec Claude Code (extension VS Code)

## Stack
- Laravel 12 (PHP 8.2) + Blade + Vite
- CSS natif (pas de framework), Tailwind gardé uniquement pour son reset (Preflight)
- Base de données : MariaDB 10.4 (le « MySQL » de XAMPP), base `jf_coaching` (utf8mb4_unicode_ci), connexion Laravel `mariadb`
- Architecture : single-page avec sections ancrées (#services, #contact...)

## Installation sur une nouvelle machine
1. `composer install` puis `npm install`
2. Copier `.env.example` en `.env`, puis `php artisan key:generate`, et renseigner les identifiants SMTP
3. Démarrer MySQL dans XAMPP, créer la base : `C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -u root -e "CREATE DATABASE jf_coaching CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"`
4. `php artisan migrate --seed` (le bloc `DB_*` de `.env.example` est déjà configuré pour XAMPP)

## Règles métier (réservation de cours)
- Réservation possible jusqu'au début du cours ; s'il reste de la place → confirmée, sinon → liste d'attente (illimitée)
- Annulation possible jusqu'à 24 h avant le début du cours ; une place confirmée libérée → la 1re personne en attente est promue

## Commandes
- Lancer le serveur Laravel : `php artisan serve`
- Lancer Vite (CSS/JS en hot-reload) : `npm run dev`
- Démarrer MySQL dans le panneau XAMPP (`C:\xampp\xampp-control.exe`)
- Lancer le worker de la queue (envoie les mails de réservation) : `php artisan queue:work` ; le redémarrer après toute modification de code, il garde l'ancienne version en mémoire
- Les quatre doivent tourner en parallèle pendant le développement
- Lancer les tests (SQLite en mémoire, ne touche pas à la base) : `php artisan test`
- Repartir d'une base propre (supprime tout, recrée, remplit) : `php artisan migrate:fresh --seed`
- Client MySQL en ligne de commande : `C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -u root jf_coaching`

## Fonctionnalités en place
- Formulaire de contact (#contact) : ContactRequest (validation) → ContactController → Mailable ContactMessage
- Envoi via SMTP Gmail (mot de passe d'application dans .env), destinataire : MAIL_CONTACT_ADDRESS
- Anti-spam : throttle:5,10 sur la route ; débloquer en local avec `php artisan cache:clear`
- Agenda des cours collectifs (#cours) : HomeController → Course::upcoming()->withBookingCounts() → carrousel de pages de 4 (scroll-snap + resources/js/carousel.js)
- Modèles : Course hasMany Booking ; Booking.status = enum BookingStatus (confirmed / waiting) ; cancel_token généré à la création
- Réservation : modale <dialog> (resources/js/booking-dialog.js) → POST /cours/{course}/reservations → BookingRequest → BookingController → action App\Actions\BookCourse (transaction + lockForUpdate)
- Champs du formulaire de réservation préfixés `booking[...]` et flash `booking_success` / `booking_error` : ne pas mélanger avec le formulaire de contact
- Annulation : lien à jeton reçu par mail → GET /annulation/{cancel_token} (page de confirmation, ne modifie rien) → POST → CancellationController → action App\Actions\CancelBooking (transaction + lockForUpdate, promotion du 1er en attente)
- Mails BookingReceived / BookingPromoted : ShouldQueueAfterCommit (queue `database`, table jobs), layout commun `emails/layout` en styles en ligne + tables ; liens construits avec APP_URL (doit valoir http://127.0.0.1:8000 en local)
- Tester les mails avec ses propres adresses (alias Gmail `+1`, `+2`) : les emails du seeder sont fictifs
- Interface coach (lot 4) : à venir

## Bon à savoir
- `php artisan serve` redémarre tout seul quand `.env` change : attendre 1-2 s avant de tester
- Client `mysql` : utiliser `-h 127.0.0.1`, avec `localhost` la connexion est refusée (erreur 2002)
- Ne jamais nommer une table `sessions` : elle existe déjà (sessions Laravel, `SESSION_DRIVER=database`) → les cours s'appellent `courses`
- Sous PowerShell, git écrit ses messages normaux sur stderr : un « exit code » d'erreur ne veut pas forcément dire que la commande a échoué

## Conventions
- Branches : `main` / `dev` / `feat/xxx`, jamais de travail direct sur `main`
- Un commit par étape validée, message clair
- Variables CSS centralisées dans `resources/css/app.css` (`:root { --color-... }`)
- Polices : Oswald (titres), Inter (texte courant), Pinyon Script (uniquement le logo "JF Coaching")
- Responsive : breakpoint unique à 768px en `max-width` (CSS écrit desktop first), media query placée juste après la règle qu'elle modifie
- Toute nouvelle section doit être vérifiée en mode téléphone avant d'être validée

## Façon de travailler
- Pour toute nouvelle fonctionnalité : proposer un plan d'abord, attendre mon accord, puis avancer étape par étape en m'expliquant le pourquoi de chaque choix
- Ne jamais pousser sur GitHub sans mon accord, ne jamais toucher à `main`
- Ne jamais écrire d'identifiants (SMTP, clés) ailleurs que dans `.env`
- Approche mentor/pédagogique : expliquer le concept avant d'écrire du code, pas de gros bloc de code non expliqué
- Avancer étape par étape, valider chaque résultat avant de continuer
- Les changements de direction en cours de route sont normaux et acceptés
- On commente les lignes quand c'est des nouveaux concepts