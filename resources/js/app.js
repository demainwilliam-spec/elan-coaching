import './carousel';
import './booking-dialog';
import './photo-carousel';
import './reveal';
import './service-links';

// Inclut les images dans le build de Vite (copiées dans public/build avec une empreinte dans le nom),
// pour que Vite::asset('resources/images/...') les trouve dans les vues Blade
import.meta.glob('../images/**');
