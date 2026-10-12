<?php
/** Modifier un service - Module GMAT */
session_start();
require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

if (!in_array($_SESSION['role_id'], [ROLE_ADMIN, ROLE_GESTIONNAIRE])) {
    header('Location: ../../login.php');
    exit;
}

$page_title = 'Modifier le service';
$page_id = 'services_modifier';

if (!isset($_GET['id']) || !intval($_GET['id'])) {
    header('Location: liste.php');
    exit;
}

$id = intval($_GET['id']);
$message = '';
$message_type = '';

try {
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();

    if (!$service) {
        header('Location: liste.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: liste.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $responsable = trim($_POST['responsable'] ?? '');
    $localisation = trim($_POST['localisation'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;

    if (empty($code) || empty($nom)) {
        $message = 'Le code et le nom du service sont obligatoires.';
        $message_type = 'danger';
    } else {
        try {
            $db = getDb();

            $stmt = $db->prepare("SELECT COUNT(*) FROM services WHERE code = ? AND id != ?");
            $stmt->execute([$code, $id]);
            if ($stmt->fetchColumn() > 0) {
                $message = 'Un autre service avec ce code existe déjà.';
                $message_type = 'danger';
            } else {
                $stmt = $db->prepare("UPDATE services SET code = ?, nom = ?, description = ?, responsable = ?, localisation = ?, actif = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$code, $nom, $description, $responsable, $localisation, $actif, $id]);

                $message = 'Service modifié avec succès.';
                $message_type = 'success';
                header('Refresh: 2; url=liste.php');
            }
        } catch (Exception $e) {
            $message = 'Erreur: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../../includes/header.php'; ?>

    <main class="main-content" style="margin-left: 260px; padding: 2rem 2rem 3rem 2rem; max-width: 1200px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-pencil me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <a href="liste.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour</a>
            </div>
            <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5>Informations du service</h5></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <label for="code" class="col-md-4 col-form-label fw-medium">Code du service *</label>
                            <div class="col-md-8"><input type="text" class="form-control" id="code" name="code" required maxlength="20" value="<?php echo htmlspecialchars($service['code']); ?>"></div>
                        </div>
                        <div class="row mb-3">
                            <label for="nom" class="col-md-4 col-form-label fw-medium">Nom du service *</label>
                            <div class="col-md-8"><input type="text" class="form-control" id="nom" name="nom" required maxlength="100" value="<?php echo htmlspecialchars($service['nom']); ?>"></div>
                        </div>
                        <div class="row mb-3">
                            <label for="responsable" class="col-md-4 col-form-label fw-medium">Responsable</label>
                            <div class="col-md-8"><input type="text" class="form-control" id="responsable" name="responsable" maxlength="100" value="<?php echo htmlspecialchars($service['responsable'] ?? ''); ?>"></div>
                        </div>
                        <div class="row mb-3">
                            <label for="localisation" class="col-md-4 col-form-label fw-medium">Localisation</label>
                            <div class="col-md-8"><input type="text" class="form-control" id="localisation" name="localisation" maxlength="100" value="<?php echo htmlspecialchars($service['localisation'] ?? ''); ?>"></div>
                        </div>
                        <div class="row mb-3">
                            <label for="description" class="col-md-4 col-form-label fw-medium">Description</label>
                            <div class="col-md-8"><textarea class="form-control" id="description" name="description" rows="3" maxlength="500"><?php echo htmlspecialchars($service['description'] ?? ''); ?></textarea></div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-md-4 col-form-label fw-medium">Statut</label>
                            <div class="col-md-8"><div class="form-check"><input class="form-check-input" type="checkbox" id="actif" name="actif" <?php echo $service['actif'] ? 'checked' : ''; ?>><label class="form-check-label" for="actif">Service actif</label></div></div>
                        </div>
                        <div class="row">
                            <label class="col-md-4 col-form-label">&nbsp;</label>
                            <div class="col-md-8">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Enregistrer</button>
                                <a href="liste.php" class="btn btn-outline-secondary"><i class="bi bi-x me-2"></i>Annuler</a>
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