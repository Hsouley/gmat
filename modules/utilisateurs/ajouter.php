<?php
/** Ajouter un utilisateur - Module GMAT */
session_start();
require_once __DIR__ . '/../../config/config.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
if ($_SESSION['role_id'] != ROLE_ADMIN) { header('Location: ../../login.php'); exit; }
$page_title = 'Nouvel utilisateur';
$message = $message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 2);
    $password = trim($_POST['password'] ?? '');
    if (empty($username) || empty($email)) {
        $message = 'Nom et email requis.'; $message_type = 'danger';
    } else {
        $message = 'Utilisateur enregistré (simulation).'; $message_type = 'success';
    }
}
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
<h2><i class="bi bi-person-plus me-2"></i><?php echo $page_title; ?></h2>
<a href="liste.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour</a>
</div>
<?php if ($message): ?><div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body">
<form method="POST">
<div class="row mb-3"><label class="col-md-3 fw-medium">Nom</label><div class="col-md-6"><input type="text" class="form-control" name="username" required></div></div>
<div class="row mb-3"><label class="col-md-3 fw-medium">Email</label><div class="col-md-6"><input type="email" class="form-control" name="email" required></div></div>
<div class="row mb-3"><label class="col-md-3 fw-medium">Mot de passe</label><div class="col-md-6"><input type="password" class="form-control" name="password" required></div></div>
<div class="row mb-3"><label class="col-md-3 fw-medium">Rôle</label><div class="col-md-6"><select class="form-control" name="role_id"><option value="1">Admin</option><option value="2">Gestionnaire</option></select></div></div>
<div class="row"><label class="col-md-3"></label><div class="col-md-6"><button class="btn btn-primary" type="submit">Enregistrer</button></div></div>
</form>
</div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
