// Formulaires à confirmer (ex. « Retirer » un inscrit) : le texte de la question est dans l'attribut data-confirm.
// Un script écrit dans la page (onsubmit="…") serait bloqué par la Content-Security-Policy, d'où ce fichier.

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        // confirm() : fenêtre OK / Annuler du navigateur ; Annuler → preventDefault() bloque l'envoi
        if (! window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});
