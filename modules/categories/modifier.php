<?php
/** Modifier une catégorie - Module GMAT */
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

$page_title = 'Modifier la catégorie';
$page_id = 'categories_modifier';

// Vérifier que l'ID est fourni
if (!isset($_GET['id']) || !intval($_GET['id'])) {
    header('Location: liste.php');
    exit;
}

$id = intval($_GET['id']);

$message = '';
$message_type = '';

// Récupérer la catégorie
try {
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $categorie = $stmt->fetch();

    if (!$categorie) {
        header('Location: liste.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Category fetch error: ' . $e->getMessage());
    header('Location: liste.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;

    if (empty($nom)) {
        $message = 'Le nom de la catégorie est obligatoire.';
        $message_type = 'danger';
    } else {
        try {
            // Vérifier l'unicité du nom (en excluant la catégorie courante)
            $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE nom = ? AND id != ?");
            $stmt->execute([$nom, $id]);

            if ($stmt->fetchColumn() > 0) {
                $message = 'Une autre catégorie avec ce nom existe déjà.';
                $message_type = 'danger';
            } else {
                // Mise à jour
                $stmt = $db->prepare("UPDATE categories SET nom = ?, description = ?, actif = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$nom, $description, $actif, $id]);

                $message = 'Catégorie modifiée avec succès.';
                $message_type = 'success';

                // Actualiser la catégorie
                $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $categorie = $stmt->fetch();

                header('Refresh: 2; url=liste.php');
            }
        } catch (PDOException $e) {
            error_log('Category update error: ' . $e->getMessage());
            $message = 'Erreur lors de la modification de la catégorie.';
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


    <div class="row">
        <main class="col-12 col-md-8 offset-md-2">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-pencil me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
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
                                       value="<?php echo htmlspecialchars($categorie['nom']); ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="description" class="col-md-4 col-form-label fw-medium">Description</label>
                            <div class="col-md-8">
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          maxlength="255"><?php echo htmlspecialchars($categorie['description']); ?></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="actif" class="col-md-4 col-form-label fw-medium">Statut</label>
                            <div class="col-md-8">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="actif" name="actif" <?php echo $categorie['actif'] ? 'checked'; ?>>
                                    <label class="form-check-label" for="actif">Catégorie active</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <label class="col-md-4 col-form-label">&nbsp;</label>
                            <div class="col-md-8">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-save me-2"></i>Enregistrer les modifications
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