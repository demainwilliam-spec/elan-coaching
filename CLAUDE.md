# Projet : JF Coaching — site vitrine coaching sportif

## Historique
- 11/10/2026 : début du travail avec Claude Code (extension VS Code)
- 28/10/2026 : lot 5 « avant le lancement » (pages légales, erreurs, SEO, polices locales, nettoyage planifié, audit)
- 31/10/2026 : retours du coach après la présentation (2 cartes de services, texte de présentation à côté des photos)
- 30/10/2026 : style noir et blanc alterné, carrousel photo, logo et favicon ; animations, cartes de services cliquables, liens Instagram / Facebook
- 31/10 – 01/11/2026 : ville (Éghezée) pour le référencement local ; comptes clients (branche feat/comptes-clients)
- 02/11/2026 : h1 « Coach sportif à Éghezée » (« Reprenez votre élan » n'était qu'un brouillon) ; texte de présentation du coach ; coordonnées du coach (config/coach.php), lieux des séances, données structurées ; annulation jusqu'à midi le jour du cours (au lieu de 24 h avant) ; planning type du coach et génération des cours (branche feat/planning-type)

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
5. Créer le compte du coach : `php artisan coach:create` (à lancer soi-même : le mot de passe est saisi dans le terminal)

## Règles métier (réservation de cours)
- Réservation possible jusqu'au début du cours ; s'il reste de la place → confirmée, sinon → liste d'attente (illimitée)
- Annulation en ligne possible jusqu'à midi le jour du cours (`Course::cancellationDeadline()`, jamais après le début du cours ; le coach ne donne pas de cours le matin) ; une place confirmée libérée → la 1re personne en attente est promue
- Le coach peut retirer quelqu'un sans limite d'heure (la personne est prévenue), inscrire quelqu'un à la main (mêmes règles qu'en ligne)
- Modifier un cours : capacité jamais sous le nombre de confirmés ; capacité augmentée → promotions ; date / durée / lieu changés → mail à tous les inscrits
- Supprimer un cours → mail à tous les inscrits, avec un message facultatif du coach
- Compte client facultatif : on peut toujours réserver sans compte ; un compte = une place par cours
- Réservation en un clic seulement avec une adresse confirmée ; sinon formulaire prérempli (réservation rattachée au compte à la confirmation)
- Une réservation (avec ou sans compte) est rattachée au compte confirmé de même adresse
- Compte supprimé (par le client ou pour inactivité) → ses réservations restent, détachées du compte (client_id null)

## Commandes
- Lancer le serveur Laravel : `php artisan serve`
- Lancer Vite (CSS/JS en hot-reload) : `npm run dev`
- Démarrer MySQL dans le panneau XAMPP (`C:\xampp\xampp-control.exe`)
- Lancer le worker de la queue (envoie les mails de réservation) : `php artisan queue:work` ; le redémarrer après toute modification de code, il garde l'ancienne version en mémoire
- Les quatre doivent tourner en parallèle pendant le développement
- Lancer les tests (SQLite en mémoire, ne touche pas à la base) : `php artisan test`
- Repartir d'une base propre (supprime tout, recrée, remplit) : `php artisan migrate:fresh --seed`
- Client MySQL en ligne de commande : `C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -u root jf_coaching`
- Prévenir les clients inactifs (lancée chaque jour par le planificateur) : `php artisan clients:warn-inactive`
- Exécuter du code Laravel ponctuel : `php artisan tinker --execute="..."` (sans --execute, tinker attend une saisie et bloque)

## Fonctionnalités en place
- Formulaire de contact (#contact) : ContactRequest (validation) → ContactController → Mailable ContactMessage
- Envoi via SMTP Gmail (mot de passe d'application dans .env), destinataire : MAIL_CONTACT_ADDRESS
- Anti-spam : throttle:5,10 sur la route ; débloquer en local avec `php artisan cache:clear`
- Agenda des cours collectifs (#cours) : HomeController → Course::upcoming()->withBookingCounts() → carrousel de pages de 4 (scroll-snap + resources/js/carousel.js)
- Modèles : Course hasMany Booking ; Booking.status = enum BookingStatus (confirmed / waiting) ; cancel_token généré à la création
- Réservation : modale <dialog> (resources/js/booking-dialog.js) → POST /cours/{course}/reservations → BookingRequest → BookingController → action App\Actions\BookCourse (transaction + lockForUpdate)
- Champs du formulaire de réservation préfixés `booking[...]` et flash `booking_success` / `booking_error` : ne pas mélanger avec le formulaire de contact
- Annulation : lien à jeton reçu par mail → GET /annulation/{cancel_token} (page de confirmation, ne modifie rien) → POST → CancellationController → action App\Actions\CancelBooking (transaction + lockForUpdate, promotion du 1er en attente)
- Promotion de la liste d'attente centralisée dans App\Actions\PromoteWaiting (utilisée par CancelBooking et UpdateCourse)
- Mails (tous ShouldQueueAfterCommit, queue `database`, table jobs) : BookingReceived, BookingPromoted, CourseUpdated reçoivent un Booking ; CourseCancelled et BookingRemoved reçoivent des valeurs simples (le modèle est supprimé avant l'envoi)
- Layout commun des mails `emails/layout` en styles en ligne + tables ; liens construits avec APP_URL (doit valoir http://127.0.0.1:8000 en local)
- Tester les mails avec ses propres adresses (alias Gmail `+1`, `+2`) : les emails du seeder sont fictifs
- Interface coach (/coach, non liée depuis le site, `noindex`) : connexion (Coach\LoginController, throttle:5,1, « rester connecté ») ; tableau de bord ; créer / modifier / dupliquer / supprimer un cours (Coach\CourseController, actions UpdateCourse / DeleteCourse) ; inscrits d'un cours (Coach\BookingController, alias `CoachBookingController` dans routes/web.php)
- Planning type (/coach/planning, bouton à côté de « Nouveau cours ») : créneaux habituels du coach (table `course_templates` : `weekday` ISO 1 = lundi … 7 = dimanche, `start_time` en `time`, secondes ajoutées par un mutateur) ; CRUD Coach\CourseTemplateController + CourseTemplateRequest (hérite de CourseRequest, un seul créneau par jour et par heure) ; vrais créneaux dans CourseTemplateSeeder (rejouable, `updateOrCreate`) : mardi 18h30 Cardio training, mercredi 18h00 et 19h10, jeudi 18h00 Circuit training, 26 places
- Génération des cours : formulaire « du … au … » sur la page du planning → action App\Actions\GenerateCourses (transaction) ; « du » prérempli au lendemain du dernier cours de l'agenda (prolonge sans recréer les cours supprimés exprès) ; cours déjà existant à la même date et heure sauté ; créneau déjà passé ignoré ; 6 mois maximum (`CourseTemplateController::GENERATION_MAX_MONTHS`) ; les cours générés sont indépendants du modèle
- Pages légales (Route::view, sans contrôleur) : /mentions-legales (dont conditions de réservation) et /confidentialite (RGPD, Belgique) ; liens dans le footer et sous les formulaires ; infos manquantes signalées par `<mark class="todo">[À COMPLÉTER…]</mark>` (il ne reste que l'hébergeur)
- Coordonnées du coach (nom, BCE / TVA, email, téléphone, adresse, lieux des séances) centralisées dans `config/coach.php`, lues avec `config('coach.…')` : mentions légales, confidentialité, contact, cartes de services (lieu sous la description, `.service-place`), données structurées, lieu prérempli d'un nouveau cours (salle des cours collectifs)
- Pages d'erreur : `resources/views/errors/{404,419,429,500,503}.blade.php` sur un gabarit commun `errors/layout` (la 500 n'apparaît qu'avec APP_DEBUG=false)
- SEO : meta description (par défaut dans `layouts/app`, surchargeable par `@section('description')`), Open Graph, canonical ; /sitemap.xml généré par une route ; robots.txt bloque /coach, /annulation/ et /compte ; `@section('noindex', true)` ajoute la balise noindex dans `layouts/app`
- Polices hébergées sur le site via les paquets npm Fontsource (importées en haut de app.css) : aucun appel à Google Fonts (RGPD)
- Tâches planifiées (routes/console.php), chaque jour : `model:prune` (Booking MassPrunable : réservations des cours passés depuis plus de 6 mois, `Booking::RETENTION_MONTHS` ; Client MassPrunable : comptes non confirmés après 7 jours, comptes inactifs), `queue:prune-failed --hours=168`, `auth:clear-resets clients` (jetons de mot de passe expirés) et `clients:warn-inactive` ; en local `php artisan schedule:work` si besoin
- Comptes clients (/compte, `noindex`, bloqué dans robots.txt) :
  - guard `client` + provider `clients` + broker `clients` (config/auth.php), table `clients` séparée de `users` (coach) ; modèle Client (MustVerifyEmail, MassPrunable) ; Booking.client_id nullable, `nullOnDelete`
  - guards toujours nommés : `auth:web` / `guest:web` côté coach, `auth:client` / `guest:client` côté client, `auth('client')->user()` / `$request->user('client')` dans le code ; sans nom, le guard par défaut peut basculer sur `client` pendant une requête
  - redirections selon l'adresse (/coach… ou non) dans bootstrap/app.php
  - contrôleurs dans App\Http\Controllers\Account : RegisterController, LoginController (alias `AccountLoginController`, `?retour=cours` comparé à une valeur fixe contre l'open redirect), EmailVerificationController, PasswordResetController, AccountController (Mon compte + tableau « Mes réservations »), ProfileController
  - vérification de l'email : lien signé 60 min (`URL::temporarySignedRoute`, hash de l'adresse), valable sans être connecté ; action App\Actions\VerifyClientEmail (rattache les réservations de même adresse, fait passer les réservations à venir sur une nouvelle adresse)
  - mot de passe oublié : broker `clients`, table `client_password_reset_tokens` ; le lien confirme aussi l'adresse ; changement de mot de passe depuis le profil = même lien par mail (pas de formulaire)
  - profil : nom / email (mot de passe actuel exigé pour changer d'email, alerte EmailChanged à l'ancienne adresse), suppression du compte ; formulaires séparés par error bags (`validateWithBag`)
  - mails : VerifyAccountEmail, ResetPasswordEmail, EmailChanged, InactiveAccountWarning (méthodes `sendEmailVerificationNotification` / `sendPasswordResetNotification` de Client remplacées par nos Mailables)
  - inactivité : `last_active_at` mis à jour par `Client::markActive()` (événement Login dans AppServiceProvider, y compris « rester connecté », et réservation avec le compte) ; avertissement après 11 mois, suppression 30 jours après l'avertissement (`Client::INACTIVE_RETENTION_MONTHS`, `INACTIVE_WARNING_DAYS`)
  - affichage : icône compte dans le header, invitation sous l'agenda, modale de réservation selon visiteur / client confirmé / non confirmé, agenda « Inscrit ✓ / En liste d'attente » ; classes CSS `.account-*`, tableau `.data-table` (empilé sur téléphone via `data-label`), grille `.form-row`, `.visually-hidden`
- À faire plus tard : historique des cours passés (« Mes réservations » ne montre que les cours à venir) ; prévenir les personnes restées en liste d'attente après la limite de midi (planificateur) ; éventuellement indiquer dans les inscrits du coach qui a un compte
- Thème noir et blanc alterné : noir par défaut (`:root`), sections blanches avec la classe `.theme-light` qui redéfinit les mêmes variables (Services, Contact) ; pages légales, d'erreur et espace coach en noir ; mails en noir et blanc (couleurs écrites en dur, les messageries ignorent les variables CSS)
- Présentation « Votre coach » sous l'accueil (section `.about`, grille 2 colonnes : texte à gauche, carrousel à droite ; empilés sur téléphone, texte d'abord) ; texte définitif fourni par le coach (Éducation Physique, coaching postural), à garder tel quel hormis la mention d'Éghezée
- Carrousel photo (dans `.about`, `role="region"`) : liste `$photos` en haut de `home.blade.php`, images WebP dans `resources/images` servies par `Vite::asset()` (incluses au build via `import.meta.glob` dans app.js), noir et blanc par `filter: grayscale`, cadre 4:5, photo en largeur en `object-fit: contain` (`landscape`), défilement auto 5 s (resources/js/photo-carousel.js), arrêt au survol / focus, désactivé si « moins d'animations » ; pas de bouton pause (choix assumé)
- Logo : `resources/images/logo.webp` (blanc sur fond transparent, 160 px de haut = 2× l'affichage maximal) dans le header, le footer et l'espace coach ; favicon `public/favicon.ico` (16/32/48) et `public/apple-touch-icon.png` (180), sur fond noir
- Animations (toutes coupées si « moins d'animations », prefers-reduced-motion) :
  - apparition au défilement : attribut `data-reveal` → resources/js/reveal.js (IntersectionObserver) ajoute `is-pending` aux éléments sous l'écran au chargement, puis `is-revealed` qui lance `@keyframes reveal` ; cartes de services en cascade via `--reveal-delay` (annulé sur téléphone) ; sans JS rien n'est caché
  - cartes de services (2 : coaching individuel → #contact, cours collectifs → #cours) : la carte avec `data-service` préremplit le message (resources/js/service-links.js préremplit le message selon `data-service`, sans écraser un texte tapé) ; survol (souris seulement, `@media (hover: hover)`) et focus clavier = couleurs inversées + montée + ombre
  - barres de remplissage des cours : `@keyframes fill-bar` (scaleX) à l'apparition de l'agenda, rejouées à chaque page par carousel.js avec `element.animate()`
- Réseaux : `partials/social-links.blade.php` (Instagram, Facebook ; icônes SVG en `currentColor`, simples liens sans widget Meta) inclus dans le header et le footer
- Photos actuelles = captures basse résolution (444 à 689 px) : avec les originaux HD, passer le carrousel en pleine largeur et ajouter `srcset` (plusieurs tailles)
- Référencement local : le coach est basé à Éghezée (province de Namur) ; la ville figure dans le `<title>` et le h1 de l'accueil (« Coach sportif à Éghezée »), la description par défaut et le texte de présentation (vérifié par SeoTest) ; « coaching postural » dans la description (mot-clé plus précis que « coach sportif ») ; données structurées `LocalBusiness` (JSON-LD) sur l'accueil seulement, poussées dans le `<head>` par `@push('head')` / `@stack('head')`, construites avec `json_encode` (+ `JSON_HEX_TAG`) ; à vérifier une fois en ligne avec le test des résultats enrichis de Google
- En attente d'infos du coach : slogan éventuel (à placer sous le h1), photos HD, image de partage og:image (1200×630), accord du client visible sur la photo `stretch` (droit à l'image), fichier vectoriel d'origine du logo (.svg / .ai / .eps) s'il existe

## Lot Déploiement (à faire)
- Hébergeur à choisir ; le compléter dans les mentions légales et la page de confidentialité
- `.env` de production : `APP_ENV=production`, `APP_DEBUG=false` (sinon les erreurs affichent le code et les variables), `APP_URL=https://...`
- HTTPS (Let's Encrypt), puis en-têtes de sécurité (HSTS, X-Frame-Options, CSP...) et durées de cache des fichiers statiques
- Worker de la queue permanent (supervisor ou équivalent), sinon aucun mail ne part
- `MAIL_CONTACT_ADDRESS` = adresse du coach (jftheys@hotmail.com) dans le `.env` de production
- Cron chaque minute : `php artisan schedule:run`, sinon les tâches planifiées ne tournent jamais
- `npm run build` sur le serveur (ou envoyer `public/build`), compte du coach via `php artisan coach:create`, planning type via `php artisan db:seed --class=CourseTemplateSeeder` (surtout pas `--seed` seul : il ajouterait les cours de démonstration)
- Ajouter `Sitemap: https://<domaine>/sitemap.xml` dans robots.txt ; déclarer le site dans Google Search Console
- Sauvegarde régulière de la base
- Vérifier la carte de partage (WhatsApp, Facebook) une fois en ligne

## Bon à savoir
- `php artisan serve` redémarre tout seul quand `.env` change : attendre 1-2 s avant de tester
- Client `mysql` : utiliser `-h 127.0.0.1`, avec `localhost` la connexion est refusée (erreur 2002)
- Ne jamais nommer une table `sessions` : elle existe déjà (sessions Laravel, `SESSION_DRIVER=database`) → les cours s'appellent `courses`
- Sous PowerShell, git écrit ses messages normaux sur stderr : un « exit code » d'erreur ne veut pas forcément dire que la commande a échoué
- `$deleteWhenMissingModels` ne marche pas sur un Mailable (seulement sur une classe Job) : un mail dont la réservation a été supprimée avant l'envoi part directement dans failed_jobs, sans nouvel essai ; c'est accepté, le planificateur vide failed_jobs
- Lighthouse : le lancer avec Vite arrêté et après `npm run build` (en dev, les fichiers non compressés faussent la note de performance), en navigation privée ; dernier résultat 98/100/100/100 (30/10/2026, avec photos et animations) ; si « Minify JavaScript ~200 Ko » ou « 3rd parties » apparaissent, c'est que Vite tournait encore
- Conversion d'images : PHP de XAMPP sans WebP → utiliser sharp (Node) depuis un dossier temporaire, hors des dépendances du projet ; seules les images converties sont commitées
- Les navigateurs gardent le favicon en cache : le vérifier en navigation privée
- Vérifier les failles des dépendances avant une mise en ligne : `npm audit` et `composer audit`
- MariaDB refuse un UPDATE d'une table avec une sous-requête sur cette même table (erreur 1093), alors que SQLite (tests) l'accepte : lire la liste à part d'abord (cf. VerifyClientEmail) et vérifier ce genre de requête sur la vraie base
- `update()` / `create()` ignorent sans erreur les colonnes absentes de `$fillable` (ex. `email_verified_at`) : utiliser `forceFill()` quand c'est voulu
- Changer de mot de passe invalide les cookies « rester connecté » des autres appareils (`remember_token` renouvelé), mais pas une session déjà ouverte ailleurs : elle expire après 2 h d'inactivité (`SESSION_LIFETIME`)
- Se déconnecter (coach ou client) vide toute la session : un coach connecté dans le même navigateur qu'un client est déconnecté aussi (accepté)

## Conventions
- Branches : `main` / `dev` / `feat/xxx`, jamais de travail direct sur `main`
- Un commit par étape validée, message clair
- Variables CSS centralisées dans `resources/css/app.css` (`:root { --color-... }`) ; jamais de couleur en dur dans le CSS du site, sinon `.theme-light` ne peut pas l'inverser
- Polices : Oswald (titres), Inter (texte courant) ; le logo est une image
- Responsive : breakpoint unique à 768px en `max-width` (CSS écrit desktop first), media query placée juste après la règle qu'elle modifie ; seules autres media queries admises : `(hover: hover)` et `(prefers-reduced-motion: reduce)`
- Toute animation doit respecter « moins d'animations » et ne jamais cacher de contenu sans JS
- Toute nouvelle section doit être vérifiée en mode téléphone avant d'être validée

## Façon de travailler
- Pour toute nouvelle fonctionnalité : proposer un plan d'abord, attendre mon accord, puis avancer étape par étape en m'expliquant le pourquoi de chaque choix
- Ne jamais pousser sur GitHub sans mon accord, ne jamais toucher à `main`
- Ne jamais écrire d'identifiants (SMTP, clés) ailleurs que dans `.env`
- Approche mentor/pédagogique : expliquer le concept avant d'écrire du code, pas de gros bloc de code non expliqué
- Avancer étape par étape, valider chaque résultat avant de continuer
- Les changements de direction en cours de route sont normaux et acceptés
- On commente les lignes quand c'est des nouveaux concepts