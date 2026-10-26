# Projet : JF Coaching — site vitrine coaching sportif

## Historique
- 11/10/2026 : début du travail avec Claude Code (extension VS Code)

## Stack
- Laravel 12 (PHP 8.2) + Blade + Vite
- CSS natif (pas de framework), Tailwind gardé uniquement pour son reset (Preflight)
- Base de données : MariaDB 10.4 (le « MySQL » de XAMPP), base `jf_coaching` (utf8mb4_unicode_ci), connexion Laravel `mariadb`
- Architecture : single-page avec sections ancrées (#services, #contact...)

## Commandes
- Lancer le serveur Laravel : `php artisan serve`
- Lancer Vite (CSS/JS en hot-reload) : `npm run dev`
- Démarrer MySQL dans le panneau XAMPP (`C:\xampp\xampp-control.exe`)
- Les trois doivent tourner en parallèle pendant le développement
- Repartir d'une base propre (supprime tout, recrée, remplit) : `php artisan migrate:fresh --seed`
- Client MySQL en ligne de commande : `C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -u root jf_coaching`

## Fonctionnalités en place
- Formulaire de contact (#contact) : ContactRequest (validation) → ContactController → Mailable ContactMessage
- Envoi via SMTP Gmail (mot de passe d'application dans .env), destinataire : MAIL_CONTACT_ADDRESS
- Anti-spam : throttle:5,10 sur la route ; débloquer en local avec `php artisan cache:clear`

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