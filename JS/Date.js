
  const today = new Date().toISOString().split("T")[0];
  document.getElementById("start").setAttribute("min", today);