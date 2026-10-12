<?php
/** Formulaire d'enregistrement d'un retour - Module GMAT */
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

$page_title = 'Enregistrement d\'un retour';
$page_id = 'retours_ajouter';

$message = '';
$message_type = '';

try {
    $db = getDb();

    // Récupérer les affectations en cours (quantité affectée > 0)
    $affectations = $db->query("
        SELECT a.id, a.code_interne, a.quantite_affectee, a.date_affectation,
               m.designation, m.quantite_disponible, m.quantite_affectee, m.etat,
               s.nom as service_nom, s.code as service_code
        FROM affectations a
        JOIN materiels m ON m.id = a.materiel_id
        JOIN services s ON s.id = a.service_id
        WHERE a.quantite_affectee > 0
        ORDER BY a.created_at DESC
    ")->fetchAll();

} catch (Exception $e) {
    error_log('Retour add error: ' . $e->getMessage());
    $affectations = [];
    $message = "Erreur lors du chargement des données.";
    $message_type = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $affectation_id = intval($_POST['affectation_id'] ?? 0);
    $materiel_id = intval($_POST['materiel_id'] ?? 0);
    $quantite_retournee = intval($_POST['quantite_retournee'] ?? 1);
    $etat_retour = trim($_POST['etat_retour'] ?? '');
    $observations = trim($_POST['observations'] ?? '');
    $date_retour = trim($_POST['date_retour'] ?? date('Y-m-d'));

    $errors = [];
    if ($affectation_id <= 0) $errors[] = 'L\'affectation doit être sélectionnée.';
    if ($quantite_retournee <= 0) $errors[] = 'La quantité retournée doit être positive.';
    if (empty($date_retour)) $errors[] = 'La date de retour est obligatoire.';
    if (empty($etat_retour)) $errors[] = 'L\'état du matériel retourné est obligatoire.';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Vérifier la quantité affectée restante (FOR UPDATE)
            $stmt = $db->prepare("SELECT quantite_affectee, materiel_id FROM affectations WHERE id = ? FOR UPDATE");
            $stmt->execute([$affectation_id]);
            $affectation = $stmt->fetch();

            if (!$affectation) {
                throw new Exception('Affectation non trouvée.');
            }

            $materiel_id = $affectation['materiel_id'];

            if ($quantite_retournee > $affectation['quantite_affectee']) {
                throw new Exception('Quantité de retour (' . $quantite_retournee . ') supérieure à la quantité encore affectée (' . $affectation['quantite_affectee'] . ').');
            }

            // Déterminer si on remet en stock (quantité disponible augmente)
            // Si le matériel est en panne ou mauvais état, on ne remet pas en stock
            $remet_en_stock = false;
            if ($etat_retour === 'Neuf' || $etat_retour === 'Très bon état' || $etat_retour === 'Bon état') {
                $remet_en_stock = true;
            }

            // Mettre à jour l'affectation
            $nouvelle_affectee = $affectation['quantite_affectee'] - $quantite_retournee;
            $stmt = $db->prepare("UPDATE affectations SET quantite_affectee = ? WHERE id = ?");
            $stmt->execute([$nouvelle_affectee, $affectation_id]);

            // Mettre à jour le matériel
            if ($remet_en_stock) {
                $stmt = $db->prepare("UPDATE materiels SET quantite_disponible = quantite_disponible + ? WHERE id = ?");
                $stmt->execute([$quantite_retournee, $materiel_id]);
            }

            // Mettre à jour l'état si nécessaire
            if (!empty($etat_retour)) {
                $stmt = $db->prepare("UPDATE materiels SET etat = ? WHERE id = ?");
                $stmt->execute([$etat_retour, $materiel_id]);
            }

            // Enregistrer le retour
            $stmt = $db->prepare("
                INSERT INTO retours (affectation_id, materiel_id, quantite_retournee, etat_retour, date_retour, utilisateur_id, observations, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$affectation_id, $materiel_id, $quantite_retournee, $etat_retour, $date_retour, $_SESSION['user_id'], $observations]);

            // Historique
            $stmt = $db->prepare("
                INSERT INTO historique_materiels (materiel_id, evenement, utilisateur_id, quantite, service_id, reference_operation, observations, date_heure)
                VALUES (?, 'retour', ?, ?, NULL, ?, ?, NOW())
            ");
            $stmt->execute([
                $materiel_id, $_SESSION['user_id'], $quantite_retournee, 'Retour au stock', 'Retour de ' . $quantite_retournee . ' unité(s) du service'
            ]);

            // Mouvement
            $stmt = $db->prepare("INSERT INTO mouvements (materiel_id, type_operation, quantite, utilisateur_id, observations, date_mouvement) VALUES (?, 'retour', ?, ?, ?, NOW())");
            $stmt->execute([$materiel_id, $quantite_retournee, $_SESSION['user_id'], 'Retour de ' . $quantite_retournee . ' unité(s)']);

            $db->commit();

            $message = 'Retour enregistré avec succès.';
            $message_type = 'success';

            header('Refresh: 2; url=liste.php');

        } catch (Exception $e) {
            $db->rollBack();
            error_log('Retour add error: ' . $e->getMessage());
            $message = 'Erreur: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    }
}

$etats = ['Neuf', 'Très bon état', 'Bon état', 'État moyen', 'Mauvais état', 'En panne'];
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
                <h2><i class="bi bi-arrow-return-left me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <a href="liste.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Détails du retour</h5></div>
                <div class="card-body">
                    <form method="POST" action="" id="formRetour">
                        <div class="row mb-3">
                            <label for="affectation_id" class="col-md-4 col-form-label fw-medium">Affectation à retourner *</label>
                            <div class="col-md-8">
                                <select class="form-select" id="affectation_id" name="affectation_id" required onchange="this.form.submit()">
                                    <option value="">Sélectionner une affectation</option>
                                    <?php foreach ($affectations as $a): ?>
                                        <option value="<?php echo $a['id']; ?>"
                                                data-materiel="<?php echo $a['materiel_id'] ?? ''; ?>"
                                                <?php echo (isset($_POST['affectation_id']) && $_POST['affectation_id'] == $a['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($a['code_interne'] . ' - ' . $a['designation'] . ' / ' . $a['service_nom'] . ' (' . $a['quantite_affectee'] . ' restant)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Les affectations actives sont listées ci-dessus.</div>
                            </div>
                        </div>

                        <?php if (!empty($affectations)): ?>
                            <input type="hidden" name="materiel_id" value="">

                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label fw-medium">Matériel</label>
                                <div class="col-md-8">
                                    <span id="infoMateriel" class="fw-bold"></span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="quantite_retournee" class="col-md-4 col-form-label fw-medium">Quantité retournée *</label>
                                <div class="col-md-8">
                                    <input type="number" class="form-control" id="quantite_retournee" name="quantite_retournee" required min="1" max="" value="1">
                                    <div class="form-text">Quantité à retourner (ne doit pas dépasser la quantité affectée restante)</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="date_retour" class="col-md-4 col-form-label fw-medium">Date de retour *</label>
                                <div class="col-md-8">
                                    <input type="date" class="form-control" id="date_retour" name="date_retour" required value="<?php echo htmlspecialchars($_POST['date_retour'] ?? date('Y-m-d')); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="etat_retour" class="col-md-4 col-form-label fw-medium">État du matériel retourné *</label>
                                <div class="col-md-8">
                                    <select class="form-select" id="etat_retour" name="etat_retour" required>
                                        <?php foreach ($etats as $etat): ?>
                                            <option value="<?php echo $etat; ?>" <?php echo (isset($_POST['etat_retour']) && $_POST['etat_retour'] === $etat) ? 'selected' : ''; ?>>
                                                <?php echo $etat; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        Si le matériel est remis en stock, sélectionnez Neuf / Très bon état / Bon état.<br>
                                        Si le matériel est défectueux, sélectionnez l'état réel.
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="observations" class="col-md-4 col-form-label fw-medium">Observations</label>
                                <div class="col-md-8">
                                    <textarea class="form-control" id="observations" name="observations" rows="3"
                                              placeholder="Observations sur le retour (état, responsable, etc...)"><?php echo htmlspecialchars($_POST['observations'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <label class="col-md-4 col-form-label">&nbsp;</label>
                                <div class="col-md-8">
                                    <button type="submit" class="btn btn-success btn-lg me-2" id="btnSubmit" disabled>
                                        <i class="bi bi-save me-2"></i>Valider le retour
                                    </button>
                                    <a href="liste.php" class="btn btn-outline-secondary btn-lg">
                                        <i class="bi bi-x me-2"></i>Annuler
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-arrow-return-left fs-1 text-muted opacity-50 mb-3"></i>
                                <p class="text-muted">Aucune affectation en cours à retourner. Veuillez d'abord créer des affectations.</p>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
// JavaScript pour afficher les informations du matériel sélectionné
const affectationSelect = document.getElementById('affectation_id');
const quantiteInput = document.getElementById('quantite_retournee');
const materielHidden = document.querySelector('input[name="materiel_id"]');
const infoMateriel = document.getElementById('infoMateriel');
const btnSubmit = document.getElementById('btnSubmit');

function updateInfo() {
    const selected = affectationSelect.options[affectationSelect.selectedIndex];
    if (selected && selected.value) {
        const matId = selected.getAttribute('data-materiel');
        materielHidden.value = matId;

        // Trouver l'objet dans le tableau PHP
        const affectations = <?php echo json_encode($affectations); ?>;
        const a = affectations.find(x => x.id == selected.value);
        if (a) {
            infoMateriel.innerHTML = a.designation + ' — Quantité affectée restante: <strong>' + a.quantite_affectee + '</strong> — État: <strong>' + a.etat + '</strong>';
            quantiteInput.max = a.quantite_affectee;
            btnSubmit.disabled = false;
        }
    } else {
        infoMateriel.innerHTML = '';
        quantiteInput.max = '';
        materielHidden.value = '';
        btnSubmit.disabled = true;
    }
}

affectationSelect.addEventListener('change', updateInfo);

// Vérifier au chargement
if (affectationSelect.value) {
    updateInfo();
}
</script>
</body>
</html>