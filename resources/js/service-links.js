// Cartes de services : le lien (#contact) fait déjà descendre au formulaire ; ici on préremplit le message.
// On n'écrase jamais un message tapé par la personne : seulement un champ vide, ou un texte prérempli par une autre carte.

const message = document.querySelector('#message');
const links = document.querySelectorAll('[data-service]');

if (message) {
    let prefilled = ''; // dernier texte mis par une carte

    links.forEach((link) => link.addEventListener('click', () => {
        if (message.value.trim() !== '' && message.value !== prefilled) return;

        // dataset.service : lit l'attribut data-service du lien
        prefilled = `Bonjour, je suis intéressé(e) par ${link.dataset.service}. `;
        message.value = prefilled;
    }));
}
