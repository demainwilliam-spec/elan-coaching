// Apparition au défilement : les éléments [data-reveal] situés sous l'écran au chargement
// sont cachés (classe is-pending), puis glissent en place quand on arrive dessus (transition CSS).
// Sans JS, ou avec « moins d'animations » demandé, rien n'est jamais caché.

const elements = document.querySelectorAll('[data-reveal]');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (elements.length && !reducedMotion && 'IntersectionObserver' in window) {
    // IntersectionObserver : le navigateur nous prévient quand un élément entre dans l'écran
    // (bien plus léger que de recalculer les positions à chaque mouvement de la molette)
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.replace('is-pending', 'is-revealed'); // lance l'animation CSS
            observer.unobserve(entry.target); // une seule fois : on arrête de le surveiller
        });
    }, {
        // rootMargin : réduit la zone de l'écran de 10 % en bas → l'élément s'anime
        // quand il est vraiment entré dans l'écran, pas dès son premier pixel
        rootMargin: '0px 0px -10% 0px',
    });

    elements.forEach((element) => {
        // Déjà visible au chargement (ou au-dessus, après un retour sur #contact) : on n'y touche pas,
        // sinon il clignoterait ou apparaîtrait en retard
        if (element.getBoundingClientRect().top < window.innerHeight) return;

        element.classList.add('is-pending');
        observer.observe(element);
    });
}
