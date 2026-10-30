# Projet : JF Coaching — site vitrine coaching sportif

## Historique
- 11/10/2026 : début du travail avec Claude Code (extension VS Code)
- 28/10/2026 : lot 5 « avant le lancement » (pages légales, erreurs, SEO, polices locales, nettoyage planifié, audit)
- 30/10/2026 : style noir et blanc alterné, carrousel photo, logo et favicon ; animations, cartes de services cliquables, liens Instagram / Facebook

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
- Annulation possible jusqu'à 24 h avant le début du cours ; une place confirmée libérée → la 1re personne en attente est promue
- Le coach peut retirer quelqu'un sans limite des 24 h (la personne est prévenue), inscrire quelqu'un à la main (mêmes règles qu'en ligne)
- Modifier un cours : capacité jamais sous le nombre de confirmés ; capacité augmentée → promotions ; date / durée / lieu changés → mail à tous les inscrits
- Supprimer un cours → mail à tous les inscrits, avec un message facultatif du coach

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
- Promotion de la liste d'attente centralisée dans App\Actions\PromoteWaiting (utilisée par CancelBooking et UpdateCourse)
- Mails (tous ShouldQueueAfterCommit, queue `database`, table jobs) : BookingReceived, BookingPromoted, CourseUpdated reçoivent un Booking ; CourseCancelled et BookingRemoved reçoivent des valeurs simples (le modèle est supprimé avant l'envoi)
- Layout commun des mails `emails/layout` en styles en ligne + tables ; liens construits avec APP_URL (doit valoir http://127.0.0.1:8000 en local)
- Tester les mails avec ses propres adresses (alias Gmail `+1`, `+2`) : les emails du seeder sont fictifs
- Interface coach (/coach, non liée depuis le site, `noindex`) : connexion (Coach\LoginController, throttle:5,1, « rester connecté ») ; tableau de bord ; créer / modifier / dupliquer / supprimer un cours (Coach\CourseController, actions UpdateCourse / DeleteCourse) ; inscrits d'un cours (Coach\BookingController, alias `CoachBookingController` dans routes/web.php)
- Pages légales (Route::view, sans contrôleur) : /mentions-legales (dont conditions de réservation) et /confidentialite (RGPD, Belgique) ; liens dans le footer et sous les formulaires ; infos manquantes signalées par `<mark class="todo">[À COMPLÉTER…]</mark>`
- Pages d'erreur : `resources/views/errors/{404,419,429,500,503}.blade.php` sur un gabarit commun `errors/layout` (la 500 n'apparaît qu'avec APP_DEBUG=false)
- SEO : meta description (par défaut dans `layouts/app`, surchargeable par `@section('description')`), Open Graph, canonical ; /sitemap.xml généré par une route ; robots.txt bloque /coach et /annulation/
- Polices hébergées sur le site via les paquets npm Fontsource (importées en haut de app.css) : aucun appel à Google Fonts (RGPD)
- Tâches planifiées (routes/console.php), chaque jour : `model:prune` (Booking MassPrunable : réservations des cours passés depuis plus de 6 mois, `Booking::RETENTION_MONTHS`) et `queue:prune-failed --hours=168` ; en local `php artisan schedule:work` si besoin
- À faire plus tard : historique des cours passés ; prévenir les personnes restées en liste d'attente après la limite des 24 h (planificateur)
- Thème noir et blanc alterné : noir par défaut (`:root`), sections blanches avec la classe `.theme-light` qui redéfinit les mêmes variables (Services, Contact) ; pages légales, d'erreur et espace coach en noir ; mails en noir et blanc (couleurs écrites en dur, les messageries ignorent les variables CSS)
- Carrousel photo sous l'accueil (section sans titre) : liste `$photos` en haut de `home.blade.php`, images WebP dans `resources/images` servies par `Vite::asset()` (incluses au build via `import.meta.glob` dans app.js), noir et blanc par `filter: grayscale`, cadre 4:5, photo en largeur en `object-fit: contain` (`landscape`), défilement auto 5 s (resources/js/photo-carousel.js), arrêt au survol / focus, désactivé si « moins d'animations » ; pas de bouton pause (choix assumé)
- Logo : `resources/images/logo.webp` (blanc sur fond transparent, 160 px de haut = 2× l'affichage maximal) dans le header, le footer et l'espace coach ; favicon `public/favicon.ico` (16/32/48) et `public/apple-touch-icon.png` (180), sur fond noir
- Animations (toutes coupées si « moins d'animations », prefers-reduced-motion) :
  - apparition au défilement : attribut `data-reveal` → resources/js/reveal.js (IntersectionObserver) ajoute `is-pending` aux éléments sous l'écran au chargement, puis `is-revealed` qui lance `@keyframes reveal` ; cartes de services en cascade via `--reveal-delay` (annulé sur téléphone) ; sans JS rien n'est caché
  - cartes de services : liens vers #contact (resources/js/service-links.js préremplit le message selon `data-service`, sans écraser un texte tapé) ; survol (souris seulement, `@media (hover: hover)`) et focus clavier = couleurs inversées + montée + ombre
  - barres de remplissage des cours : `@keyframes fill-bar` (scaleX) à l'apparition de l'agenda, rejouées à chaque page par carousel.js avec `element.animate()`
- Réseaux : `partials/social-links.blade.php` (Instagram, Facebook ; icônes SVG en `currentColor`, simples liens sans widget Meta) inclus dans le header et le footer
- Photos actuelles = captures basse résolution (444 à 689 px) : avec les originaux HD, passer le carrousel en pleine largeur et ajouter `srcset` (plusieurs tailles)
- En attente d'infos du coach : ville (à ajouter au titre et à la description pour le référencement local), infos légales (statut, BCE, TVA, adresse, email, téléphone), photos HD, image de partage og:image (1200×630), accord du client visible sur la photo `stretch` (droit à l'image), fichier vectoriel d'origine du logo (.svg / .ai / .eps) s'il existe

## Lot Déploiement (à faire)
- Hébergeur à choisir ; le compléter dans les mentions légales et la page de confidentialité
- `.env` de production : `APP_ENV=production`, `APP_DEBUG=false` (sinon les erreurs affichent le code et les variables), `APP_URL=https://...`
- HTTPS (Let's Encrypt), puis en-têtes de sécurité (HSTS, X-Frame-Options, CSP...) et durées de cache des fichiers statiques
- Worker de la queue permanent (supervisor ou équivalent), sinon aucun mail ne part
- Cron chaque minute : `php artisan schedule:run`, sinon les tâches planifiées ne tournent jamais
- `npm run build` sur le serveur (ou envoyer `public/build`), compte du coach via `php artisan coach:create`
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