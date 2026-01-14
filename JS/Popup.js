function togglePopup() {
    const popup = document.getElementById("popup-overlay");
    if (popup.classList.contains("active")) {
        popup.classList.remove("active");
    } else {
        popup.classList.add("active");
    }
}

function openPopup(){
    const popup = document.getElementById("popup-overlay");
    if (popup) popup.classList.add("active");
}

function closePopup(){
    const popup = document.getElementById("popup-overlay");
    if (popup) popup.classList.remove("active");
}