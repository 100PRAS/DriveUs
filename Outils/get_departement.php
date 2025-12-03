<?php
$pdo = new PDO("mysql:host=localhost;dbname=ta_base;charset=utf8", "root", "");

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $sql = $pdo->prepare("SELECT departement_nom FROM departement WHERE departement_code = ?");
    $sql->execute([$code]);
    $row = $sql->fetch();

    if ($row) {
        echo htmlspecialchars($row['departement_nom']);
    } else {
        echo "";
    }
}
?>
