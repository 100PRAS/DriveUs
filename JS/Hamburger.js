const hamburger = document.querySelector(".hamburger");
const bande = document.querySelector(".Bande");

// Abort if the menu is not present on the page
if (hamburger && bande) {
    // Toggle menu au clic sur le hamburger
    hamburger.addEventListener("click", (e) => {
        e.stopPropagation();
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

    // Fermer le menu au redimensionnement vers desktop
    window.addEventListener("resize", () => {
        if (window.innerWidth >= 1200) {
            hamburger.classList.remove("active");
            bande.classList.remove("active");
            bande.style.display = "";
        }
    });

    // Fermer le menu quand on clique en dehors
    document.addEventListener("click", (e) => {
        const isClickInsideHamburger = e.target.closest(".hamburger");
        const isClickInsideBande = e.target.closest(".Bande");
        
        if (!isClickInsideHamburger && !isClickInsideBande) {
            hamburger.classList.remove("active");
            bande.classList.remove("active");
        }
    });

    // Empêcher la fermeture si on clique sur la Bande
    bande.addEventListener("click", (e) => {
        e.stopPropagation();
    });

    console.log("✓ Hamburger menu initialized");
}