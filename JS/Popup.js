function togglePopup() {
    const popup = document.getElementById("popup-overlay");
    popup.style.display = (popup.style.display === "none" || popup.style.display === "") ? "block" : "none";
    
}

function openPopup(){
    document.getElementById("popup").style.displlay="block";
    document.getElementById("overlay").style.displlay="block";

}

function closePopup(){
    document.getElementById("popup").style.displlay="none";
    document.getElementById("overlay").style.displlay="none";

}