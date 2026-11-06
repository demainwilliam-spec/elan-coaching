// Carrousel photo sous l'accueil : défilement automatique toutes les 5 s, points de navigation.
// Pas de bouton pause (choix assumé) : arrêt au survol / focus, et aucun défilement si « moins d'animations ».
// Le glissement au doigt marche sans JS grâce au scroll-snap du CSS.

const carousel = document.querySelector('[data-photo-carousel]');

if (carousel) {
    const track = carousel.querySelector('[data-photo-track]');
    const dots = carousel.querySelectorAll('[data-photo-dot]');
    const count = track.children.length;
    const DELAY = 5000; // millisecondes

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let timer = null;     // identifiant renvoyé par setInterval, pour pouvoir l'arrêter

    const current = () => Math.round(track.scrollLeft / track.clientWidth);

    const goTo = (index) => {
        track.scrollTo({ left: index * track.clientWidth, behavior: reducedMotion ? 'auto' : 'smooth' });
    };

    // aria-current : indique aux lecteurs d'écran (et au CSS) quel point correspond à la photo affichée
    const updateDots = () => {
        dots.forEach((dot, i) => dot.setAttribute('aria-current', i === current() ? 'true' : 'false'));
    };

    // % count : après la dernière photo, on revient à la première
    const next = () => goTo((current() + 1) % count);

    const start = () => {
        // Moins d'animations demandé → pas de défilement automatique du tout
        if (reducedMotion || timer) return;
        timer = setInterval(next, DELAY);
    };

    const stop = () => {
        clearInterval(timer);
        timer = null;
    };

    dots.forEach((dot, i) => dot.addEventListener('click', () => {
        goTo(i);
        // On relance le compte à rebours : sinon la photo choisie pourrait partir aussitôt
        stop();
        start();
    }));

    // Pause pendant que la souris est sur le carrousel ou qu'un de ses boutons a le focus (clavier)
    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', start);

    // Sur téléphone (pas de survol) : après un glissement au doigt, on repart pour 5 s complètes.
    // passive : promet au navigateur qu'on n'empêche pas le défilement, il reste fluide
    track.addEventListener('touchstart', () => { stop(); start(); }, { passive: true });

    track.addEventListener('scroll', updateDots);

    updateDots();
    start();
}
