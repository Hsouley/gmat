<?php
/** Ajouter un nouveau matériel - Module GMAT */
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

$page_title = 'Nouveau matériel';
$page_id = 'materiels_ajouter';

$message = '';
$message_type = '';

try {
    $db = getDb();
    $categories = $db->query("SELECT id, nom FROM categories WHERE actif = 1 ORDER BY nom")->fetchAll();
} catch (Exception $e) {
    $categories = [];
}

$etats = ['Neuf', 'Très bon état', 'Bon état', 'État moyen', 'Mauvais état', 'En panne'];

// Générer un code interne unique
function genererCodeInterne($db) {
    $prefix = 'MAT-';
    $maxAttempts = 10;

    for ($i = 0; $i < $maxAttempts; $i++) {
        $num = mt_rand(10000, 99999);
        $code = $prefix . $num;

        $stmt = $db->prepare("SELECT COUNT(*) FROM materiels WHERE code_interne = ?");
        $stmt->execute([$code]);

        if ($stmt->fetchColumn() == 0) {
            return $code;
        }
    }

    // Fallback
    $stmt = $db->query("SELECT MAX(CAST(REPLACE(code_interne, 'MAT-', '') AS UNSIGNED)) FROM materiels");
    $max = $stmt->fetchColumn() ?: 9999;
    return 'MAT-' . ($max + 1);
}

$code_interne = genererCodeInterne($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code_interne = trim($_POST['code_interne'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $categorie_id = intval($_POST['categorie_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $marque = trim($_POST['marque'] ?? '');
    $modele = trim($_POST['modele'] ?? '');
    $numero_serie = trim($_POST['numero_serie'] ?? '');
    $quantite = intval($_POST['quantite'] ?? 1);
    $etat = trim($_POST['etat'] ?? 'Neuf');
    $date_entree = trim($_POST['date_entree'] ?? date('Y-m-d'));
    $provenance = trim($_POST['provenance'] ?? '');
    $observations = trim($_POST['observations'] ?? '');

    // Validation
    $errors = [];
    if (empty($code_interne)) $errors[] = 'Le code interne est obligatoire.';
    if (empty($designation)) $errors[] = 'La désignation est obligatoire.';
    if ($categorie_id <= 0) $errors[] = 'La catégorie est obligatoire.';
    if ($quantite <= 0) $errors[] = 'La quantité doit être positive.';
    if (!in_array($etat, $etats)) $errors[] = 'État invalide.';

    if (empty($errors)) {
        try {
            // Vérifier l'unicité du code interne
            $stmt = $db->prepare("SELECT COUNT(*) FROM materiels WHERE code_interne = ?");
            $stmt->execute([$code_interne]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Ce code interne existe déjà.';
            }
        } catch (Exception $e) {
            $errors[] = 'Erreur de vérification: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            // Transaction pour l'insertion
            $db->beginTransaction();

            // Générer le code-barres (format Code 128)
            $code_barre = $code_interne;

            // Insérer le matériel
            $stmt = $db->prepare("
                INSERT INTO materiels
                (code_interne, designation, categorie_id, marque, modele, numero_serie,
                 quantite_totale, quantite_disponible, quantite_affectee, etat, date_entree, provenance, observations, code_barre, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $code_interne, $designation, $categorie_id, $marque, $modele, $numero_serie,
                $quantite, $quantite, $etat, $date_entree, $provenance, $observations, $code_barre
            ]);

            $materiel_id = $db->lastInsertId();

            // Enregistrer l'entrée de stock
            $stmt = $db->prepare("
                INSERT INTO entrees_stock (materiel_id, quantite, date_entree, fournisseur, observations, utilisateur_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$materiel_id, $quantite, $date_entree, $provenance, $observations, $_SESSION['user_id']]);

            // Enregistrer dans l'historique
            $stmt = $db->prepare("
                INSERT INTO historique_materiels (materiel_id, evenement, utilisateur_id, quantite, service_id, reference_operation, observations, date_heure)
                VALUES (?, 'creation', ?, ?, NULL, ?, ?, NOW())
            ");
            $stmt->execute([$materiel_id, $_SESSION['user_id'], $quantite, $code_interne, 'Création du matériel: ' . $designation]);

            // Enregistrer le mouvement
            $stmt = $db->prepare("
                INSERT INTO mouvements (materiel_id, type_operation, quantite, utilisateur_id, observations, date_mouvement)
                VALUES (?, 'entree', ?, ?, ?, NOW())
            ");
            $stmt->execute([$materiel_id, $quantite, $_SESSION['user_id'], 'Entrée initiale']);

            $db->commit();

            $message = 'Matériel ajouté avec succès. Code interne: ' . $code_interne;
            $message_type = 'success';

            // Rediriger vers la fiche
            header('Refresh: 2; url=fiche.php?id=' . $materiel_id);

        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Materiel add error: ' . $e->getMessage());
            $message = 'Erreur lors de l\'ajout: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
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
                <h2><i class="bi bi-plus-circle me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <a href="liste.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour à la liste</a>
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
                    <form method="POST" action="" id="formMateriel">
                        <div class="row mb-3">
                            <label for="code_interne" class="col-md-3 col-form-label fw-medium">Code interne *</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="code_interne" name="code_interne" required maxlength="50"
                                       value="<?php echo htmlspecialchars($_POST['code_interne'] ?? $code_interne); ?>">
                                <div class="form-text">Généré automatiquement (format: MAT-XXXXX)</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="designation" class="col-md-3 col-form-label fw-medium">Désignation *</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="designation" name="designation" required maxlength="200"
                                       value="<?php echo htmlspecialchars($_POST['designation'] ?? ''); ?>">
                                <div class="form-text">Nom complet du matériel (ex: Ordinateur portable HP EliteBook 840 G8)</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="categorie_id" class="col-md-3 col-form-label fw-medium">Catégorie *</label>
                            <div class="col-md-9">
                                <select class="form-select" id="categorie_id" name="categorie_id" required>
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo (isset($_POST['categorie_id']) && $_POST['categorie_id'] == $c['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="description" class="col-md-3 col-form-label fw-medium">Description</label>
                            <div class="col-md-9">
                                <textarea class="form-control" id="description" name="description" rows="2" maxlength="500"
                                          placeholder="Description détaillée du matériel..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="text-muted mb-3"><i class="bi bi-tag me-2"></i>Informations techniques</h6>

                        <div class="row mb-3">
                            <label for="marque" class="col-md-3 col-form-label fw-medium">Marque</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="marque" name="marque" maxlength="100"
                                       value="<?php echo htmlspecialchars($_POST['marque'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="modele" class="col-md-3 col-form-label fw-medium">Modèle</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="modele" name="modele" maxlength="100"
                                       value="<?php echo htmlspecialchars($_POST['modele'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="numero_serie" class="col-md-3 col-form-label fw-medium">Numéro de série</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="numero_serie" name="numero_serie" maxlength="100"
                                       value="<?php echo htmlspecialchars($_POST['numero_serie'] ?? ''); ?>">
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="text-muted mb-3"><i class="bi bi-cubes me-2"></i>Quantités et état</h6>

                        <div class="row mb-3">
                            <label for="quantite" class="col-md-3 col-form-label fw-medium">Quantité entrée *</label>
                            <div class="col-md-9">
                                <input type="number" class="form-control" id="quantite" name="quantite" required min="1"
                                       value="<?php echo htmlspecialchars($_POST['quantite'] ?? 1); ?>">
                                <div class="form-text">Quantité d'unités entrées en stock</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="etat" class="col-md-3 col-form-label fw-medium">État *</label>
                            <div class="col-md-9">
                                <select class="form-select" id="etat" name="etat" required>
                                    <?php foreach ($etats as $e): ?>
                                        <option value="<?php echo $e; ?>" <?php echo (isset($_POST['etat']) && $_POST['etat'] === $e) || (!isset($_POST['etat']) && $e === 'Neuf') ? 'selected' : ''; ?>>
                                            <?php echo $e; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="date_entree" class="col-md-3 col-form-label fw-medium">Date d'entrée *</label>
                            <div class="col-md-9">
                                <input type="date" class="form-control" id="date_entree" name="date_entree" required
                                       value="<?php echo htmlspecialchars($_POST['date_entree'] ?? date('Y-m-d')); ?>">
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="text-muted mb-3"><i class="bi bi-truck me-2"></i>Montant d'acquisition</h6>

                        <div class="row mb-3">
                            <label for="provenance" class="col-md-3 col-form-label fw-medium">Montant d'acquisition *</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="provenance" name="provenance" maxlength="200"
                                       value="<?php echo htmlspecialchars($_POST['provenance'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="observations" class="col-md-3 col-form-label fw-medium">Observations</label>
                            <div class="col-md-9">
                                <textarea class="form-control" id="observations" name="observations" rows="3" maxlength="500"
                                          placeholder="Observations diverses..."><?php echo htmlspecialchars($_POST['observations'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <label class="col-md-3 col-form-label">&nbsp;</label>
                            <div class="col-md-9">
                                <button type="submit" class="btn btn-primary btn-lg me-2">
                                    <i class="bi bi-save me-2"></i>Enregistrer le matériel
                                </button>
                                <a href="liste.php" class="btn btn-outline-secondary btn-lg">
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Générer un nouveau code interne si l'utilisateur vide le champ
    const codeInput = document.getElementById('code_interne');
    codeInput.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            // Le code est déjà généré côté serveur, on ne le régénère pas
        }
    });
});
</script>
</body>
</html>