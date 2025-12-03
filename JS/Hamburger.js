
const hamburger = document.querySelector(".hamburger");
const bande = document.querySelector(".Bande");

hamburger.addEventListener("click", () => {
    bande.classList.toggle("active");
});
