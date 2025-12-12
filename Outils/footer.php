<?php
// Footer réutilisable
// Suppose que $text peut être défini par header.php, sinon fournir des valeurs par défaut.
$footerText = $text ?? [];
?>
<footer class="Pied">
    <p>© 2025 Drive Us — Partagez vos trajets, économisez et voyagez ensemble.</p>
    <p class="pC">Contact : Drive.us@gmail.com</p>
    <p class="CGU"><a href="CGU.php"><?= $footerText["CGU"] ?? "CGU" ?></a></p>
</footer>
