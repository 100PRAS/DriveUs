
const hamburger = document.querySelector(".hamburger");
const bande = document.querySelector(".Bande");

// Abort if the menu is not present on the page
if (hamburger && bande) {
    hamburger.addEventListener("click", () => {
        hamburger.classList.toggle("active");
        bande.classList.toggle("active");
    });

    // Fermer le menu si on clique sur un lien
    const bandLinks = document.querySelectorAll(".Bande a, .Bande button");
    bandLinks.forEach(link => {
        link.addEventListener("click", () => {
            hamburger.classList.remove("active");
            bande.classList.remove("active");
        });
    });

    // Fermer le menu au redimensionnement
    window.addEventListener("resize", () => {
        if (window.innerWidth > 768) {
            hamburger.classList.remove("active");
            bande.classList.remove("active");
        }
    });

    // Fermer le menu quand on clique en dehors
    document.addEventListener("click", (e) => {
        if (!e.target.closest(".hamburger") && !e.target.closest(".Bande")) {
            hamburger.classList.remove("active");
            bande.classList.remove("active");
        }
    });
}