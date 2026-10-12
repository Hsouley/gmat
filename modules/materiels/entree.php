<?php
/** Ajouter une entrée de stock pour un matériel - Module GMAT */
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

$page_title = 'Nouvelle entrée de stock';
$page_id = 'materiels_entree';

$message = '';
$message_type = '';

try {
    $db = getDb();

    // Récupérer tous les matériels pour la liste déroulante
    $materiels = $db->query("SELECT id, code_interne, designation FROM materiels ORDER BY designation")->fetchAll();

    if (!isset($_GET['materiel_id']) && empty($materiels)) {
        header('Location: liste.php');
        exit;
    }

} catch (Exception $e) {
    $materiels = [];
}

// État des matériels pour la sélection au retour
$etats = ['Neuf', 'Très bon état', 'Bon état', 'État moyen', 'Mauvais état', 'En panne'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materiel_id = intval($_POST['materiel_id'] ?? 0);
    $quantite = intval($_POST['quantite'] ?? 1);
    $date_entree = trim($_POST['date_entree'] ?? date('Y-m-d'));
    $fournisseur = trim($_POST['fournisseur'] ?? '');
    $etat_retour = trim($_POST['etat_retour'] ?? '');
    $observations = trim($_POST['observations'] ?? '');

    // Validation
    $errors = [];
    if ($materiel_id <= 0) $errors[] = 'Matériel non sélectionné.';
    if ($quantite <= 0) $errors[] = 'La quantité doit être positive.';
    if (empty($date_entree)) $errors[] = 'La date d\'entrée est obligatoire.';

    if (empty($errors)) {
        try {
            // Démarrer une transaction
            $db->beginTransaction();

            // Récupérer les informations du matériel
            $stmt = $db->prepare("SELECT m.*, c.nom as categorie_nom FROM materiels m LEFT JOIN categories c ON m.categorie_id = c.id WHERE m.id = ?");
            $stmt->execute([$materiel_id]);
            $materiel = $stmt->fetch();

            if (!$materiel) {
                throw new Exception('Matériel non trouvé.');
            }

            // Si l'état de retour est différent de l'état actuel, mettre à jour l'état
            if (!empty($etat_retour) && $etat_retour !== $materiel['etat']) {
                $stmt = $db->prepare("UPDATE materiels SET etat = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$etat_retour, $materiel_id]);

                // Enregistrer dans l'historique des changements d'état
                $stmt = $db->prepare("INSERT INTO historique_materiels (materiel_id, evenement, utilisateur_id, quantite, service_id, reference_operation, observations, date_heure)
                                      VALUES (?, 'changement_etat', ?, ?, NULL, ?, ?, NOW())");
                $stmt->execute([$materiel_id, $_SESSION['user_id'], $materiel['etat'] . ' → ' . $etat_retour, 'Changement d\'état: ' . $materiel['designation']]);
            }

            // Mettre à jour les quantités du matériel
            $nouvelle_disponible = $materiel['quantite_disponible'] + $quantite;
            $nouveau_total = $materiel['quantite_totale'] + $quantite;

            $stmt = $db->prepare("UPDATE materiels SET quantite_disponible = ?, quantite_totale = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$nouvelle_disponible, $nouveau_total, $materiel_id]);

            // Enregistrer l'entrée de stock
            $stmt = $db->prepare("INSERT INTO entrees_stock (materiel_id, quantite, date_entree, fournisseur, observations, utilisateur_id, created_at)
                                  VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$materiel_id, $quantite, $date_entree, $fournisseur, $observations, $_SESSION['user_id']]);

            // Enregistrer dans l'historique
            $stmt = $db->prepare("INSERT INTO historique_materiels (materiel_id, evenement, utilisateur_id, quantite, service_id, reference_operation, observations, date_heure)
                                  VALUES (?, 'entree', ?, ?, NULL, ?, ?, NOW())");
            $stmt->execute([$materiel_id, $_SESSION['user_id'], $quantite, $materiel['code_interne'] . ' - ' . $materiel['designation'], 'Nouvelle entrée: ' . $quantite . ' unité(s) ajoutée(s) à ' . $materiel['designation']]);

            // Enregistrer le mouvement
            $stmt = $db->prepare("INSERT INTO mouvements (materiel_id, type_operation, quantite, utilisateur_id, observations, date_mouvement)
                                  VALUES (?, 'entree', ?, ?, ?, NOW())");
            $stmt->execute([$materiel_id, $quantite, $_SESSION['user_id'], 'Entrée en stock']);

            $db->commit();

            $message = 'Entrée enregistrée avec succès.';
            $message_type = 'success';

            // Rediriger après un court délai
            header('Refresh: 2; url=fiche.php?id=' . $materiel_id);

        } catch (PDOException $e) {
            $db->rollBack();
            error_log('Materiel entree error: ' . $e->getMessage());
            $message = 'Erreur lors de l\'entrée: ' . $e->getMessage();
            $message_type = 'danger';
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Materiel entree error: ' . $e->getMessage());
            $message = 'Erreur: ' . $e->getMessage();
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


    <div class="row">
        <main class="col-12 col-md-8 offset-md-2">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-plus-square me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <a href="liste.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour à la liste</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Détails de l'entrée</h5></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <label for="materiel_id" class="col-md-4 col-form-label fw-medium">Matériel *</label>
                            <div class="col-md-8">
                                <select class="form-select" id="materiel_id" name="materiel_id" required onchange="this.form.submit()">
                                    <option value="">Sélectionner un matériel</option>
                                    <?php foreach ($materiels as $m): ?>
                                        <option value="<?php echo $m['id']; ?>"
                                                <?php echo isset($_GET['materiel_id']) && $_GET['materiel_id'] == $m['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($m['code_interne'] . ' - ' . $m['designation']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <?php if (isset($_GET['materiel_id']) && !empty($materiels)): ?>
                            <input type="hidden" name="materiel_id" value="<?php echo htmlspecialchars($_GET['materiel_id']); ?>">

                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label fw-medium">Matériel sélectionné</label>
                                <div class="col-md-8">
                                    <?php
                                    $selected_materiel_id = intval($_GET['materiel_id'] ?? 0);
                                    $stmt = $db->prepare("SELECT code_interne, designation, quantite_disponible, quantite_totale FROM materiels WHERE id = ?");
                                    $stmt->execute([$selected_materiel_id]);
                                    $selected_materiel = $stmt->fetch();
                                    if ($selected_materiel): ?>
                                        <p class="mb-0"><strong><?php echo htmlspecialchars($selected_materiel['code_interne']); ?></strong> - <?php echo htmlspecialchars($selected_materiel['designation']); ?></p>
                                        <p class="mb-0 text-muted small">
                                            Disponible: <?php echo htmlspecialchars($selected_materiel['quantite_disponible']); ?> | Total: <?php echo htmlspecialchars($selected_materiel['quantite_totale']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="quantite" class="col-md-4 col-form-label fw-medium">Quantité entrée *</label>
                                <div class="col-md-8">
                                    <input type="number" class="form-control" id="quantite" name="quantite" required min="1"
                                           value="<?php echo htmlspecialchars($_POST['quantite'] ?? 1); ?>">
                                    <div class="form-text">Nombre d'unités à ajouter au stock</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="date_entree" class="col-md-4 col-form-label fw-medium">Date d'entrée *</label>
                                <div class="col-md-8">
                                    <input type="date" class="form-control" id="date_entree" name="date_entree" required
                                           value="<?php echo htmlspecialchars($_POST['date_entree'] ?? date('Y-m-d')); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="fournisseur" class="col-md-4 col-form-label fw-medium">Fournisseur</label>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="fournisseur" name="fournisseur"
                                           value="<?php echo htmlspecialchars($_POST['fournisseur'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="etat_retour" class="col-md-4 col-form-label fw-medium">État lors du retour (optionnel)</label>
                                <div class="col-md-8">
                                    <select class="form-select" id="etat_retour" name="etat_retour">
                                        <option value="">Conserver l'état actuel</option>
                                        <?php foreach ($etats as $etat): ?>
                                            <option value="<?php echo $etat; ?>"><?php echo $etat; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Si le matériel est défectueux, sélectionnez son état actuel</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="observations" class="col-md-4 col-form-label fw-medium">Observations</label>
                                <div class="col-md-8">
                                    <textarea class="form-control" id="observations" name="observations" rows="3"
                                              placeholder="Observations sur l'entrée, le fournisseur, etc..."><?php echo htmlspecialchars($_POST['observations'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <label class="col-md-4 col-form-label">&nbsp;</label>
                                <div class="col-md-8">
                                    <button type="submit" class="btn btn-success btn-lg me-2">
                                        <i class="bi bi-save me-2"></i>Enregistrer l'entrée
                                    </button>
                                    <a href="liste.php" class="btn btn-outline-secondary btn-lg">
                                        <i class="bi bi-x me-2"></i>Annuler
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-box-seam fs-1 text-muted opacity-50 mb-3"></i>
                                <p class="text-muted">Veuillez sélectionner un matériel pour enregistrer une entrée.</p>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>