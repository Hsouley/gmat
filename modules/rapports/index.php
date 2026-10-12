<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
if (!isset($_SESSION['user_id'])) header('Location: ../../login.php?error=non_autorise');
$page_title = 'Rapports';
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title><?php echo $page_title; ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../../assets/css/style.css" rel="stylesheet"></head><body class="bg-light">
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid py-4"><main class="col-12 col-md-11 col-lg-10"><h2><i class="bi bi-file-earmark-text me-2"></i>Rapports</h2><div class="row g-3"><div class="col-md-4"><a href="#" class="card text-decoration-none p-3"><div class="h5">État des stocks</div><small class="text-muted">Rapport général sur les quantités disponibles.</small></a></div><div class="col-md-4"><a href="#" class="card text-decoration-none p-3"><div class="h5">Affectations</div><small class="text-muted">Répartition par service et période.</small></a></div><div class="col-md-4"><a href="#" class="card text-decoration-none p-3"><div class="h5">Mouvements</div><small class="text-muted">Entrées, sorties et retours.</small></a></div></div></main></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?></body></html>
