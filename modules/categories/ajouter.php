<?php
/** Ajouter une catégorie - Module GMAT */
session_start();
require_once __DIR__ . '/../../config/config.php';

// Protection des pages
if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
    header('Location: ../../login.php?error=non_autorise');
    exit;
}

$allowed_roles = [ROLE_ADMIN, ROLE_GESTIONNAIRE];
if (!in_array($_SESSION['role_id'], $allowed_roles)) {
    header('Location: ../../login.php?error=non_autorise');
    exit;
}

$page_title = 'Nouvelle catégorie';
$page_id = 'categories_ajouter';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;

    if (empty($nom)) {
        $message = 'Le nom de la catégorie est obligatoire.';
        $message_type = 'danger';
    } else {
        try {
            $db = getDb();

            // Vérifier l'unicité du nom
            $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE nom = ?");
            $stmt->execute([$nom]);
            if ($stmt->fetchColumn() > 0) {
                $message = 'Une catégorie avec ce nom existe déjà.';
                $message_type = 'danger';
            } else {
                // Insertion
                $stmt = $db->prepare("INSERT INTO categories (nom, description, actif, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$nom, $description, $actif]);

                $message = 'Catégorie ajoutée avec succès.';
                $message_type = 'success';

                // Rediriger après succès
                header('Refresh: 2; url=liste.php');
            }
        } catch (PDOException $e) {
            error_log('Category add error: ' . $e->getMessage());
            $message = 'Erreur lors de l\'ajout de la catégorie.';
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . '/../../includes/header.php'; ?>


    <main class="main-content" style="margin-left: 260px; padding: 2rem 2rem 3rem 2rem; max-width: 1200px;">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-plus-circle me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <a href="liste.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour à la liste</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                    <i class="bi bi-info-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Informations de la catégorie</h5></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <label for="nom" class="col-md-4 col-form-label fw-medium required">Nom de la catégorie *</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="nom" name="nom" maxlength="100" required
                                       value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                                <div class="form-text">Nom unique de la catégorie (ex: Matériel informatique)</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="description" class="col-md-4 col-form-label fw-medium">Description</label>
                            <div class="col-md-8">
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          maxlength="255"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <div class="form-text">Description facultative de la catégorie</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="actif" class="col-md-4 col-form-label fw-medium">Statut</label>
                            <div class="col-md-8">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="actif" name="actif" checked>
                                    <label class="form-check-label" for="actif">Catégorie active</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <label class="col-md-4 col-form-label">&nbsp;</label>
                            <div class="col-md-8">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-save me-2"></i>Enregistrer
                                </button>
                                <a href="liste.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x me-2"></i>Annuler
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>