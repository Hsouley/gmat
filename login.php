<?php
/**
 * Page de connexion - Application GMAT
 */

require_once __DIR__ . '/config/config.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $db = getDb();

            // Préparation de la requête
            $stmt = $db->prepare("
                SELECT u.*, r.nom as role_nom
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE (u.username = ? OR u.email = ?)
                AND u.actif = 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_nom'] = $user['role_nom'];
                $_SESSION['email'] = $user['email'];

                // Mettre à jour la date de dernière connexion
                $updateStmt = $db->prepare("UPDATE users SET dernier_connexion = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                // Journaliser la connexion
                error_log("Connexion réussie: " . $user['username'] . " (" . $user['role_nom'] . ")");

                header('Location: index.php');
                exit;
            } else {
                $error = 'Identifiants incorrects.';
                error_log("Échec de connexion pour: " . $username);
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Erreur de connexion. Veuillez réessayer.';
        }
    }
}

// Gestion du message de déconnexion
if (isset($_GET['logged_out']) && $_GET['logged_out'] == 1) {
    $success = 'Vous avez été déconnecté avec succès.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Connexion</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Styles personnalisés -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3 class="mb-0"><i class="bi bi-buildings me-2"></i><?php echo APP_NAME; ?></h3>
                        <p class="mb-0 small">Direction des Ressources Matérielles</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label fw-medium">Identifiant ou Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username"
                                           placeholder="Nom d'utilisateur ou email" required autofocus>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label fw-medium">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password"
                                           placeholder="••••••••" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg py-2">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-white border-top-0 text-center py-3">
                        <p class="mb-0 text-muted small">Version <?php echo APP_VERSION; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>