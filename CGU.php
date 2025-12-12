<!DOCTYPE html>
<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<html>
<head>
    <title>Drive Us - CGU</title>
    <link rel="icon" type="image/x-icon" href="Image/Icone.ico">
    <link rel="stylesheet" href="CSS/Outils/layout-global.css" />
    <link rel="stylesheet" href="CSS/CGU1.css" />
    <link rel="stylesheet" href="CSS/Sombre/Sombre_Acceuil.css" />
    <script src="JS/Sombre.js"></script>
</head>
<body>
    <?php include 'Outils/header.php'; ?>
    
    <main>
        <p class ="CGU"><b> Condition Générale d'utilisation</b></p>
    </main>
    <?php include 'Outils/footer.php'; ?>
</body>
</html>