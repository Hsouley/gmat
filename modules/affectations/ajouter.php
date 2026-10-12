<?php
/** Formulaire d'affectation - Module GMAT */
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

$page_title = 'Nouvelle affectation';
$page_id = 'affectations_ajouter';

$message = '';
$message_type = '';

try {
    $db = getDb();

    // Récupérer les matériels avec leur quantité disponible
    $materiels = $db->query("
        SELECT m.id, m.code_interne, m.designation, m.quantite_disponible, m.etat, c.nom as categorie_nom
        FROM materiels m
        LEFT JOIN categories c ON m.categorie_id = c.id
        WHERE m.quantite_disponible > 0
        ORDER BY m.designation
    ")->fetchAll();

    // Récupérer les services actifs
    $services = $db->query("SELECT id, nom, code FROM services WHERE actif = 1 ORDER BY nom")->fetchAll();

} catch (Exception $e) {
    error_log('Affectation add error: ' . $e->getMessage());
    $materiels = [];
    $services = [];
    $message = "Erreur lors du chargement des données.";
    $message_type = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materiel_id = intval($_POST['materiel_id'] ?? 0);
    $service_id = intval($_POST['service_id'] ?? 0);
    $quantite = intval($_POST['quantite'] ?? 1);
    $etat_affectation = trim($_POST['etat_affectation'] ?? '');
    $observations = trim($_POST['observations'] ?? '');
    $date_affectation = trim($_POST['date_affectation'] ?? date('Y-m-d'));

    $errors = [];
    if ($materiel_id <= 0) $errors[] = 'Le matériel doit être sélectionné.';
    if ($service_id <= 0) $errors[] = 'Le service doit être sélectionné.';
    if ($quantite <= 0) $errors[] = 'La quantité doit être positive.';
    if (empty($date_affectation)) $errors[] = 'La date d\'affectation est obligatoire.';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Vérifier la disponibilité du matériel (FOR UPDATE)
            $stmt = $db->prepare("SELECT quantite_disponible, quantite_affectee FROM materiels WHERE id = ? FOR UPDATE");
            $stmt->execute([$materiel_id]);
            $materiel = $stmt->fetch();

            if (!$materiel) {
                throw new Exception('Matériel non trouvé.');
            }

            if ($quantite > $materiel['quantite_disponible']) {
                throw new Exception('Quantité demandée (' . $quantite . ') supérieure à la quantité disponible (' . $materiel['quantite_disponible'] . ').');
            }

            // Vérifier que le service existe
            $stmt = $db->prepare("SELECT nom FROM services WHERE id = ? AND actif = 1");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();

            if (!$service) {
                throw new Exception('Service non trouvé ou inactif.');
            }

            // Récupérer code interne du matériel
            $stmt_code = $db->prepare("SELECT code_interne FROM materiels WHERE id = ?");
            $stmt_code->execute([$materiel_id]);
            $code_interne_materiel = $stmt_code->fetchColumn();

            // Insérer l'affectation
            $stmt = $db->prepare("
                INSERT INTO affectations (code_interne, materiel_id, service_id, quantite_affectee, date_affectation, utilisateur_id, observations, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $code_interne_materiel ?: '', $materiel_id, $service_id, $quantite, $date_affectation, $_SESSION['user_id'], $observations
            ]);

            // Mettre à jour le matériel
            $nouvelle_disponible = $materiel['quantite_disponible'] - $quantite;
            $nouveau_affecte = $materiel['quantite_affectee'] + $quantite;

            $stmt = $db->prepare("UPDATE materiels SET quantite_disponible = ?, quantite_affectee = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$nouvelle_disponible, $nouveau_affecte, $materiel_id]);

            // Historique
            $stmt = $db->prepare("
                INSERT INTO historique_materiels (materiel_id, evenement, utilisateur_id, quantite, service_id, reference_operation, observations, date_heure)
                VALUES (?, 'affectation', ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $materiel_id, $_SESSION['user_id'], $quantite, $service_id,
                'Affectation au service', 'Affectation de ' . $quantite . ' unité(s) au service: ' . $service['nom']
            ]);

            // Mouvement
            $stmt = $db->prepare("INSERT INTO mouvements (materiel_id, type_operation, quantite, utilisateur_id, observations, date_mouvement) VALUES (?, 'affectation', ?, ?, ?, NOW())");
            $stmt->execute([$materiel_id, $quantite, $_SESSION['user_id'], 'Affectation au service: ' . $service['nom']]);

            $db->commit();

            $message = 'Affectation enregistrée avec succès.';
            $message_type = 'success';

            // Rediriger après succès
            header('Refresh: 2; url=liste.php');

        } catch (Exception $e) {
            $db->rollBack();
            error_log('Affectation add error: ' . $e->getMessage());
            $message = 'Erreur: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    }
}

// Récupérer le code interne du matériel sélectionné
$code_interne_materiel = '';
if (isset($_POST['materiel_id']) && intval($_POST['materiel_id']) > 0) {
    $stmt = $db->prepare("SELECT code_interne FROM materiels WHERE id = ?");
    $stmt->execute([$_POST['materiel_id']]);
    $code_interne_materiel = $stmt->fetchColumn();
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
                <h2><i class="bi bi-share me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <a href="liste.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Détails de l'affectation</h5></div>
                <div class="card-body">
                    <form method="POST" action="" id="formAffectation">
                        <div class="row mb-3">
                            <label for="materiel_id" class="col-md-4 col-form-label fw-medium">Matériel *</label>
                            <div class="col-md-8">
                                <select class="form-select" id="materiel_id" name="materiel_id" required>
                                    <option value="">Sélectionner un matériel</option>
                                    <?php foreach ($materiels as $m): ?>
                                        <option value="<?php echo $m['id']; ?>"
                                                data-code="<?php echo htmlspecialchars($m['code_interne']); ?>"
                                                data-disponible="<?php echo $m['quantite_disponible']; ?>"
                                                data-etat="<?php echo htmlspecialchars($m['etat']); ?>"
                                                <?php echo (isset($_POST['materiel_id']) && $_POST['materiel_id'] == $m['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($m['code_interne'] . ' - ' . $m['designation'] . ' (' . $m['categorie_nom'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Seuls les matériels avec du stock disponible sont affichés.</div>
                            </div>
                        </div>

                        <div class="mb-3" id="infoMateriel" style="display: none;">
                            <div class="alert alert-info mb-0" id="infoMaterielContent">
                                <i class="bi bi-info-circle me-2"></i>
                                <span id="infoMaterielText"></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="service_id" class="col-md-4 col-form-label fw-medium">Service *</label>
                            <div class="col-md-8">
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <option value="">Sélectionner un service</option>
                                    <?php foreach ($services as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" <?php echo (isset($_POST['service_id']) && $_POST['service_id'] == $s['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['code'] . ' - ' . $s['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="quantite" class="col-md-4 col-form-label fw-medium">Quantité *</label>
                            <div class="col-md-8">
                                <input type="number" class="form-control" id="quantite" name="quantite" required min="1" max=""
                                       value="<?php echo htmlspecialchars($_POST['quantite'] ?? 1); ?>">
                                <div class="form-text">Quantité à affecter au service (ne doit pas dépasser la quantité disponible)</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="date_affectation" class="col-md-4 col-form-label fw-medium">Date d'affectation *</label>
                            <div class="col-md-8">
                                <input type="date" class="form-control" id="date_affectation" name="date_affectation" required
                                       value="<?php echo htmlspecialchars($_POST['date_affectation'] ?? date('Y-m-d')); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="etat_affectation" class="col-md-4 col-form-label fw-medium">État au moment de l'affectation</label>
                            <div class="col-md-8">
                                <select class="form-select" id="etat_affectation" name="etat_affectation">
                                    <option value="">Conserver l'état actuel</option>
                                    <?php foreach ($etats as $etat): ?>
                                        <option value="<?php echo $etat; ?>"><?php echo $etat; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="observations" class="col-md-4 col-form-label fw-medium">Observations</label>
                            <div class="col-md-8">
                                <textarea class="form-control" id="observations" name="observations" rows="3"
                                          placeholder="Observations sur l'affectation (utilisateur, projet, etc...)"><?php echo htmlspecialchars($_POST['observations'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <label class="col-md-4 col-form-label">&nbsp;</label>
                            <div class="col-md-8">
                                <button type="submit" class="btn btn-info btn-lg me-2" id="btnSubmit" disabled>
                                    <i class="bi bi-share me-2"></i>Valider l'affectation
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
// JavaScript pour afficher les informations du matériel sélectionné
const materielSelect = document.getElementById('materiel_id');
const infoMateriel = document.getElementById('infoMateriel');
const infoMaterielText = document.getElementById('infoMaterielText');
const quantiteInput = document.getElementById('quantite');
const btnSubmit = document.getElementById('btnSubmit');

function updateMaterielInfo() {
    const selected = materielSelect.options[materielSelect.selectedIndex];
    if (selected && selected.value) {
        const code = selected.getAttribute('data-code');
        const disponible = selected.getAttribute('data-disponible');
        const etat = selected.getAttribute('data-etat');

        infoMaterielText.innerHTML = '<strong>' + code + '</strong> — Quantité disponible: <strong>' + disponible + '</strong> — État: <strong>' + etat + '</strong>';
        infoMateriel.style.display = 'block';
        quantiteInput.max = disponible;
        btnSubmit.disabled = false;
    } else {
        infoMateriel.style.display = 'none';
        quantiteInput.value = 1;
        btnSubmit.disabled = true;
    }
}

materielSelect.addEventListener('change', updateMaterielInfo);

// Vérifier au chargement si un matériel est sélectionné
if (materielSelect.value) {
    updateMaterielInfo();
}
</script>
</body>
</html>