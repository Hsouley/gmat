<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
if (!isset($_GET['id'])) exit('ID requis');
$id = intval($_GET['id']);
$db = getDb();
$stmt = $db->prepare("SELECT * FROM materiels WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) exit('Non trouvé');
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Imprimer fiche</title><link href="../../assets/css/style.css" rel="stylesheet"></head>
<body style="padding:2rem; font-family:sans-serif;">
<h1>Fiche matériel — <?php echo htmlspecialchars($m['designation']); ?></h1>
<table style="border-collapse:collapse; width:100%;">
<tr><th style="text-align:left;">Code</th><td><?php echo htmlspecialchars($m['code_interne']); ?></td></tr>
<tr><th style="text-align:left;">Désignation</th><td><?php echo htmlspecialchars($m['designation']); ?></td></tr>
<tr><th style="text-align:left;">État</th><td><?php echo htmlspecialchars($m['etat']); ?></td></tr>
<tr><th style="text-align:left;">Quantité</th><td><?php echo $m['quantite_totale']; ?></td></tr>
</table>
<script>window.print();</script>
</body></html>
