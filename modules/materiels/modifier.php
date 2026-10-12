<?php
/** Modifier un matériel - Module GMAT */
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

$page_title = 'Modifier le matériel';
$page_id = 'materiels_modifier';

if (!isset($_GET['id']) || !intval($_GET['id'])) {
    header('Location: liste.php');
    exit;
}

$id = intval($_GET['id']);

$message = '';
$message_type = '';

try {
    $db = getDb();
    $categories = $db->query("SELECT id, nom FROM categories WHERE actif = 1 ORDER BY nom")->fetchAll();

    $stmt = $db->prepare("SELECT * FROM materiels WHERE id = ?");
    $stmt->execute([$id]);
    $materiel = $stmt->fetch();

    if (!$materiel) {
        header('Location: liste.php');
        exit;
    }

} catch (Exception $e) {
    error_log('Materiel modifier error: ' . $e->getMessage());
    header('Location: liste.php');
    exit;
}

$etats = ['Neuf', 'Très bon état', 'Bon état', 'État moyen', 'Mauvais état', 'En panne'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($_POST['description']);
    $code_interne = trim($_POST['code_interne'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $categorie_id = intval($_POST['categorie_id'] ?? 0);
    $marque = trim($_POST['marque'] ?? '');
    $modele = trim($_POST['modele'] ?? '');
    $numero_serie = trim($_POST['numero_serie'] ?? '');
    $etat = trim($_POST['etat'] ?? '');
    $provenance = trim($_POST['provenance'] ?? '');
    $observations = trim($_POST['observations'] ?? '');

    $errors = [];
    if (empty($code_interne)) $errors[] = 'Le code interne est obligatoire.';
    if (empty($designation)) $errors[] = 'La désignation est obligatoire.';
    if ($categorie_id <= 0) $errors[] = 'La catégorie est obligatoire.';
    if (!in_array($etat, $etats)) $errors[] = 'État invalide.';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Vérifier l'unicité du code interne (en excluant le matériel courant)
            $stmt = $db->prepare("SELECT COUNT(*) FROM materiels WHERE code_interne = ? AND id != ?");
            $stmt->execute([$code_interne, $id]);

            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Ce code interne existe déjà pour un autre matériel.';
            } else {
                // Mettre à jour le matériel
                $stmt = $db->prepare("UPDATE materiels SET code_interne = ?, designation = ?, categorie_id = ?, marque = ?, modele = ?, numero_serie = ?, etat = ?, provenance = ?, observations = ? WHERE id = ?");
                $stmt->execute([$code_interne, $designation, $categorie_id, $marque, $modele, $numero_serie, $etat, $provenance, $observations, $id]);

                // Historique si modification importante
                $stmt = $db->prepare("INSERT INTO historique_materiels (materiel_id, evenement, utilisateur_id, quantite, service_id, reference_operation, observations, date_heure)
                                      VALUES (?, 'modification', ?, 0, NULL, ?, ?, NOW())");
              $stmt->execute([
    $id,                                             // materiel_id
    $_SESSION['user_id'],                            // utilisateur_id
    'Modification du matériel',                      // reference_operation
    'Modification du matériel : ' . $designation     // observations
]);
                $db->commit();

                $message = 'Matériel modifié avec succès.';
                $message_type = 'success';

                // Actualiser le matériel
                $stmt = $db->prepare("SELECT * FROM materiels WHERE id = ?");
                $stmt->execute([$id]);
                $materiel = $stmt->fetch();

                header('Refresh: 2; url=fiche.php?id=' . $id);
            }

        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Materiel modifier error: ' . $e->getMessage());
            $message = 'Erreur lors de la modification: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
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
                <h2><i class="bi bi-pencil me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <a href="fiche.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour à la fiche</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Informations du matériel</h5></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <label for="code_interne" class="col-md-4 col-form-label fw-medium">Code interne *</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="code_interne" name="code_interne" required maxlength="50"
                                       value="<?php echo htmlspecialchars($materiel['code_interne']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="designation" class="col-md-4 col-form-label fw-medium">Désignation *</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="designation" name="designation" required maxlength="200"
                                       value="<?php echo htmlspecialchars($materiel['designation']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="categorie_id" class="col-md-4 col-form-label fw-medium">Catégorie *</label>
                            <div class="col-md-8">
                                <select class="form-select" id="categorie_id" name="categorie_id" required>
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"
                                                <?php echo $materiel['categorie_id'] == $c['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="text-muted mb-3"><i class="bi bi-tag me-2"></i>Informations techniques</h6>

                        <div class="row mb-3">
                            <label for="marque" class="col-md-4 col-form-label fw-medium">Marque</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="marque" name="marque" maxlength="100"
                                       value="<?php echo htmlspecialchars($materiel['marque']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="modele" class="col-md-4 col-form-label fw-medium">Modèle</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="modele" name="modele" maxlength="100"
                                       value="<?php echo htmlspecialchars($materiel['modele']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="numero_serie" class="col-md-4 col-form-label fw-medium">Numéro de série</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="numero_serie" name="numero_serie" maxlength="100"
                                       value="<?php echo htmlspecialchars($materiel['numero_serie']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="etat" class="col-md-4 col-form-label fw-medium">État *</label>
                            <div class="col-md-8">
                                <select class="form-select" id="etat" name="etat" required>
                                    <?php foreach ($etats as $etat): ?>
                                        <option value="<?php echo $etat; ?>"
                                                <?php echo $materiel['etat'] === $etat ? 'selected' : ''; ?>>
                                            <?php echo $etat; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="text-muted mb-3"><i class="bi bi-truck me-2"></i>Montant d'acquisition</h6>

                        <div class="row mb-3">
                            <label for="provenance" class="col-md-4 col-form-label fw-medium">Montant d'acquisition *</label>
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="provenance" name="provenance" maxlength="200"
                                       value="<?php echo htmlspecialchars($materiel['provenance']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="observations" class="col-md-4 col-form-label fw-medium">Observations</label>
                            <div class="col-md-8">
                                <textarea class="form-control" id="observations" name="observations" rows="3" maxlength="500"
                                          placeholder="Observations diverses...">
                                    <?php echo htmlspecialchars($materiel['observations']); ?>
                                </textarea>
                            </div>
                        </div>

                        <div class="row">
                            <label class="col-md-4 col-form-label">&nbsp;</label>
                            <div class="col-md-8">
                                <button type="submit" class="btn btn-primary btn-lg me-2">
                                    <i class="bi bi-save me-2"></i>Enregistrer les modifications
                                </button>
                                <a href="fiche.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary btn-lg">
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