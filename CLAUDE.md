# Projet : JF Coaching — site vitrine coaching sportif

## Historique
- 11/10/2026 : début du travail avec Claude Code (extension VS Code)

## Stack
- Laravel 12 (PHP 8.2) + Blade + Vite
- CSS natif (pas de framework), Tailwind gardé uniquement pour son reset (Preflight)
- Base de données : SQLite pour l'instant, passage à MySQL (XAMPP) prévu quand on en aura besoin
- Architecture : single-page avec sections ancrées (#services, #contact...)

## Commandes
- Lancer le serveur Laravel : `php artisan serve`
- Lancer Vite (CSS/JS en hot-reload) : `npm run dev`
- Les deux doivent tourner en parallèle pendant le développement

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