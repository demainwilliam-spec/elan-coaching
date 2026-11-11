<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// En-têtes de sécurité ajoutés à chaque réponse : des consignes pour le navigateur, invisibles pour le visiteur
class SecurityHeaders
{
    // Content-Security-Policy : d'où le navigateur a le droit de charger chaque type de ressource.
    // 'self' = ce site uniquement. Les styles en ligne restent permis pour les barres de remplissage
    // des cours (style="width: …%") ; les scripts, eux, ne peuvent venir que des fichiers du site
    private const CONTENT_SECURITY_POLICY = [
        "default-src 'self'",
        "script-src 'self'",
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self'",
        "font-src 'self'",
        "connect-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // $next($request) : la page est d'abord construite, puis on complète ses en-têtes
        $response = $next($request);
        $headers = $response->headers;

        // Le site ne peut pas être affiché dans le cadre (iframe) d'un autre site (piège à clics)
        $headers->set('X-Frame-Options', 'DENY');
        // Le navigateur ne « devine » pas le type d'un fichier : un texte ne sera jamais exécuté comme un script
        $headers->set('X-Content-Type-Options', 'nosniff');
        // Un lien vers un autre site (Instagram, Facebook) ne transmet que le nom du site, pas l'adresse de la page
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Le site n'utilise ni caméra, ni micro, ni position : on les bloque
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS : le navigateur retient d'utiliser HTTPS pendant 1 an. Seulement en HTTPS (jamais en local)
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // Pas de CSP en local : elle bloquerait le serveur de Vite (npm run dev). Active en production et dans les tests
        if (! app()->isLocal()) {
            $headers->set('Content-Security-Policy', implode('; ', self::CONTENT_SECURITY_POLICY));
        }

        return $response;
    }
}
