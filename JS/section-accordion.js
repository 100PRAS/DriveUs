// Gestion des accordéons pour sections du formulaire
document.addEventListener('DOMContentLoaded', function() {
    const sectionHeaders = document.querySelectorAll('.section-accordion-header');
    
    sectionHeaders.forEach((header, index) => {
        // Ouvrir la première section par défaut
        if (index === 0) {
            header.classList.add('active');
            header.nextElementSibling.classList.add('open');
        }
        
        header.addEventListener('click', function(e) {
            e.preventDefault();
            const content = this.nextElementSibling;
            const isActive = this.classList.contains('active');
            
            if (isActive) {
                this.classList.remove('active');
                content.classList.remove('open');
            } else {
                this.classList.add('active');
                content.classList.add('open');
            }
        });
    });
});
