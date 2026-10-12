<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
if (!isset($_SESSION['user_id'])) header('Location: ../../login.php?error=non_autorise');
$page_title = 'Historique';
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title><?php echo $page_title; ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../../assets/css/style.css" rel="stylesheet"></head><body class="bg-light">
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid py-4"><main class="main-content" style="margin-left: 260px; padding: 2rem 2rem 3rem 2rem; max-width: 1200px;"><h2><i class="bi bi-clock-history me-2"></i>Historique des mouvements</h2><div class="card"><table class="table"><thead><tr><th>Date</th><th>Matériel</th><th>Événement</th><th>Qté</th><th>Utilisateur</th></tr></thead><tbody><tr><td>Aucun mouvement</td><td>-</td><td>-</td><td>-</td><td>-</td></tr></tbody></table></div></main></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?></body></html>
