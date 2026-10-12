<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
if (!isset($_SESSION['user_id'])) header('Location: ../../login.php?error=non_autorise');
$page_title = 'Recherche historique';
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title><?php echo $page_title; ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../../assets/css/style.css" rel="stylesheet"></head><body class="bg-light">
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid py-4"><main class="col-12 col-md-11 col-lg-10"><h2><i class="bi bi-clock-history me-2"></i>Recherche historique</h2><p class="text-muted">Rechercher dans l'historique des mouvements.</p><div class="card"><div class="card-body"><form method="GET" action=""><input type="text" name="q" class="form-control" placeholder="Motif, matériel, utilisateur..." style="max-width:500px;display:inline-block;"><button class="btn btn-primary">Rechercher</button></form></div></div></main></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?></body></html>
