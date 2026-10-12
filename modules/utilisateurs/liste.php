<?php
/** Liste des utilisateurs - Module GMAT */
session_start();
require_once __DIR__ . '/../../config/config.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
if (!in_array($_SESSION['role_id'], [ROLE_ADMIN, ROLE_GESTIONNAIRE])) { header('Location: ../../login.php'); exit; }
$page_title = 'Utilisateurs';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="../../assets/css/style.css" rel="stylesheet">
<link href="../../assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../../includes/header.php'; ?>
<main class="main-content" style="margin-left:260px;padding:2rem 2rem 3rem 2rem;max-width:1200px;">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2><i class="bi bi-people me-2"></i>Utilisateurs</h2>
<a href="ajouter.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Ajouter</a>
</div>
<div class="card shadow-sm"><div class="card-body"><p class="text-muted">Module utilisateurs en cours. Base : <code>users</code> dans <code>database/schema.sql</code>.</p></div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
