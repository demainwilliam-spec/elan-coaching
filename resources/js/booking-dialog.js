// Modale de réservation : une seule <dialog> pour tous les cours.
// Chaque bouton "Réserver" porte les infos de son cours en data-* ; on les recopie dans la modale.

const dialog = document.querySelector('[data-booking-dialog]');

if (dialog) {
    const form = dialog.querySelector('[data-booking-form]');
    // Petit raccourci : trouve un élément de la modale par son attribut data-booking-xxx
    const part = (name) => dialog.querySelector(`[data-booking-${name}]`);

    const fill = (button) => {
        // dataset : accès aux attributs data-* (data-course-id → dataset.courseId)
        const { courseId, action, title, when, location, description, full } = button.dataset;
        const isFull = full === '1';

        form.action = action; // le formulaire sera envoyé à /cours/{id}/reservations
        part('course-id').value = courseId;

        // textContent : insère du texte brut, jamais interprété comme du HTML (protection XSS)
        part('title').textContent = title;
        part('when').textContent = when;
        part('location').textContent = location;
        part('description').textContent = description;
        part('description').hidden = !description; // pas de description → paragraphe masqué

        part('heading').textContent = isFull ? "Liste d'attente" : 'Réserver';
        part('submit').textContent = isFull ? "Rejoindre la liste d'attente" : 'Confirmer la réservation';
        part('full-notice').hidden = !isFull;
    };

    // Nouvelle ouverture par un clic : on retire les erreurs d'une tentative précédente
    const clearErrors = () => {
        dialog.querySelectorAll('.form-error, .form-alert-error').forEach((el) => el.remove());
        dialog.querySelectorAll('.has-error').forEach((el) => el.classList.remove('has-error'));
    };

    const open = (button, keepErrors = false) => {
        if (!keepErrors) {
            clearErrors();
        }
        fill(button);
        dialog.showModal(); // ouverture "modale" : fond assombri, Échap pour fermer, page derrière inerte
    };

    document.querySelectorAll('[data-booking-open]').forEach((button) => {
        button.addEventListener('click', () => open(button));
    });

    // Clic sur le fond assombri : la cible du clic est la <dialog> elle-même (le contenu est dans .booking-dialog-inner)
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    // Après une erreur, Blade a mis l'id du cours dans data-reopen-course : on rouvre sur ce cours
    const reopenId = dialog.dataset.reopenCourse;
    if (reopenId) {
        const button = document.querySelector(`[data-booking-open][data-course-id="${reopenId}"]`);
        if (button) {
            open(button, true);
        } else {
            // Le cours n'est plus dans l'agenda (il a commencé entre-temps) : on affiche l'erreur
            // au-dessus de l'agenda plutôt que dans une modale qu'on ne peut pas rouvrir
            const alert = dialog.querySelector('.form-alert-error');
            if (alert) {
                document.querySelector('.course-carousel').before(alert); // before() : déplace l'élément juste avant
            }
        }
    }
}
