// Gestion du menu accordéon pour les filtres

document.addEventListener('DOMContentLoaded', function() {
    const accordionHeaders = document.querySelectorAll('.accordion-header');
    
    accordionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const isActive = this.classList.contains('active');
            const content = this.nextElementSibling;
            
            // Fermer les autres accordéons si souhaité (déremmenter pour single-open)
            // accordionHeaders.forEach(h => {
            //     h.classList.remove('active');
            //     h.nextElementSibling.classList.remove('open');
            // });
            
            // Basculer l'accordéon actuel
            if (isActive) {
                this.classList.remove('active');
                content.classList.remove('open');
            } else {
                this.classList.add('active');
                content.classList.add('open');
            }
        });
    });
    
    // Ouvrir le premier accordéon par défaut
    if (accordionHeaders.length > 0) {
        accordionHeaders[0].classList.add('active');
        accordionHeaders[0].nextElementSibling.classList.add('open');
    }
});
