// Accordéon simple pour filtres additionnels
document.addEventListener('DOMContentLoaded', function() {
    const filterAccordionBtn = document.getElementById('filterAccordionBtn');
    const filterAccordionContent = document.getElementById('filterAccordionContent');
    
    if (filterAccordionBtn) {
        filterAccordionBtn.addEventListener('click', function() {
            filterAccordionBtn.classList.toggle('active');
            filterAccordionContent.classList.toggle('open');
        });
    }
});
