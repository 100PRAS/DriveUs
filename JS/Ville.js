const input = document.getElementById("inputVille");
const box = document.getElementById("suggestions");

input.addEventListener("input", async () => {
  const txt = input.value;

  if(txt.length < 2){ box.innerHTML = ""; return; }

  let res = await fetch("recherche.php?q="+encodeURIComponent(txt));
  let data = await res.json();

  box.innerHTML = data.map(v => `<div class="sugg">${v.nom}</div>`).join("");
});