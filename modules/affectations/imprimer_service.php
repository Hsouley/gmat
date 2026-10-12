<?php
/** Impression du matériel affecté à un service - Module GMAT */
session_start();
require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [ROLE_ADMIN, ROLE_GESTIONNAIRE])) {
    header('Location: ../../login.php');
    exit;
}

$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
if ($service_id <= 0) {
    die('Service invalide.');
}

try {
    $db = getDb();

    $stmt = $db->prepare("SELECT id, nom, code FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();
    if (!$service) {
        die('Service introuvable.');
    }

    $stmt = $db->prepare("
        SELECT a.quantite_affectee, a.date_affectation,
               m.code_interne, m.designation, m.etat as etat_materiel
        FROM affectations a
        JOIN materiels m ON m.id = a.materiel_id
        WHERE a.service_id = ? AND a.quantite_affectee > 0
        ORDER BY m.designation, m.code_interne
    ");
    $stmt->execute([$service_id]);
    $lignes = $stmt->fetchAll();

    $totalQte = array_sum(array_column($lignes, 'quantite_affectee'));
} catch (Exception $e) {
    error_log('Impression service error: ' . $e->getMessage());
    die('Erreur lors du chargement des données.');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Matériel du service <?php echo htmlspecialchars($service['code']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; margin: 20px; color: #000; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 4px; }
        .sous-titre { text-align: center; margin-bottom: 20px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background: #eee; }
        .center { text-align: center; }
        .total { margin-top: 12px; font-weight: bold; text-align: right; }
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; }
        .signatures div { width: 40%; text-align: center; }
        .no-print { text-align: center; margin-bottom: 15px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">Imprimer</button>
    <button onclick="window.close()">Fermer</button>
</div>

<h1>MATÉRIEL AFFECTÉ</h1>
<div class="sous-titre">
    Service : <strong><?php echo htmlspecialchars($service['nom'] . ' (' . $service['code'] . ')'); ?></strong><br>
    Édité le <?php echo date('d/m/Y'); ?>
</div>

<?php if (empty($lignes)): ?>
    <p class="center">Aucun matériel affecté à ce service.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th class="center">N°</th>
                <th>Code</th>
                <th>Désignation</th>
                <th class="center">Qté</th>
                <th class="center">État</th>
                <th class="center">Date d'affectation</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $i => $l): ?>
            <tr>
                <td class="center"><?php echo $i + 1; ?></td>
                <td><?php echo htmlspecialchars($l['code_interne']); ?></td>
                <td><?php echo htmlspecialchars($l['designation']); ?></td>
                <td class="center"><?php echo htmlspecialchars($l['quantite_affectee']); ?></td>
                <td class="center"><?php echo htmlspecialchars($l['etat_materiel']); ?></td>
                <td class="center"><?php echo date('d/m/Y', strtotime($l['date_affectation'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="total">
        Total : <?php echo count($lignes); ?> ligne(s) — <?php echo $totalQte; ?> unité(s)
    </div>
<?php endif; ?>

<div class="signatures">
    <div><br><br><br></div>
    <div>Le Gestionnaire du matériel<br><br><br>_______________</div>
</div>

<script>window.onload = function() { window.print(); };</script>
</body>
</html>