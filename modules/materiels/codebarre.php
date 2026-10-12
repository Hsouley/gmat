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
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Code-barres</title><link href="../../assets/css/style.css" rel="stylesheet"></head>
<body class="bg-light" style="padding:2rem; text-align:center;">
<h2>QR Code — <?php echo htmlspecialchars($m['code_interne']); ?></h2>
<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($m['code_interne']); ?>" alt="QR Code">
<p><?php echo htmlspecialchars($m['code_barre'] ?: $m['code_interne']); ?></p>
<a href="fiche.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">Retour</a>
</body>
</html>
