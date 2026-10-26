// Carrousel des cours collectifs : les flèches font défiler le rail d'une page,
// le compteur "Page X / Y" suit la position (y compris quand on glisse au doigt).

const track = document.querySelector('[data-carousel-track]');
const prev = document.querySelector('[data-carousel-prev]');
const next = document.querySelector('[data-carousel-next]');
const status = document.querySelector('[data-carousel-status]');

// Pas de navigation sur la page (une seule page de cours, ou section absente) : rien à faire
if (track && prev && next && status) {
    const pageCount = track.children.length;

    // matchMedia : lit une media query depuis le JS ; ici, l'utilisateur a-t-il demandé moins d'animations ?
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // clientWidth = largeur visible du rail = largeur d'une page
    const currentPage = () => Math.round(track.scrollLeft / track.clientWidth);

    const update = () => {
        const page = currentPage();
        status.textContent = `Page ${page + 1} / ${pageCount}`; // `...${}` : template string, insère une valeur
        prev.disabled = page === 0;
        next.disabled = page === pageCount - 1;
    };

    // direction : -1 = page précédente, +1 = page suivante
    const goTo = (direction) => {
        track.scrollBy({
            left: direction * track.clientWidth,
            behavior: reducedMotion ? 'auto' : 'smooth',
        });
    };

    prev.addEventListener('click', () => goTo(-1));
    next.addEventListener('click', () => goTo(1));

    // Déclenché à chaque défilement, qu'il vienne des flèches ou du doigt
    track.addEventListener('scroll', update);
}
