<?php
/** Fiche détaillée d'un matériel - Module GMAT */
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

$page_title = 'Fiche du matériel';
$page_id = 'materiels_fiche';

if (!isset($_GET['id']) || !intval($_GET['id'])) {
    header('Location: liste.php');
    exit;
}

$id = intval($_GET['id']);
$message = '';
$message_type = '';

try {
    $db = getDb();

    // Informations générales du matériel
    $stmt = $db->prepare("
        SELECT m.*, c.nom as categorie_nom
        FROM materiels m
        LEFT JOIN categories c ON m.categorie_id = c.id
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $materiel = $stmt->fetch();

    if (!$materiel) {
        header('Location: liste.php');
        exit;
    }

    // Affectations actives
    $stmt = $db->prepare("
        SELECT a.*, s.nom as service_nom, s.code as service_code
        FROM affectations a
        JOIN services s ON s.id = a.service_id
        WHERE a.materiel_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$id]);
    $affectations = $stmt->fetchAll();

    // Historique du matériel
    $stmt = $db->prepare("
        SELECT hm.*, u.username, s.nom as service_nom
        FROM historique_materiels hm
        LEFT JOIN users u ON u.id = hm.utilisateur_id
        LEFT JOIN services s ON s.id = hm.service_id
        WHERE hm.materiel_id = ?
        ORDER BY hm.date_heure DESC
        LIMIT 100
    ");
    $stmt->execute([$id]);
    $historique = $stmt->fetchAll();

    // Code-barres du matériel
    $codebarre = null;
    if (!empty($materiel['code_barre'])) {
        try {
            $db2 = getDb();
            $stmt2 = $db2->prepare("SELECT code FROM codebarre WHERE code = ?");
            $stmt2->execute([$materiel['code_barre']]);
            $codebarre = $stmt2->fetchColumn() !== false;
        } catch (Exception $e) {
            // Table codebarre peut ne pas exister, ignore
        }
    }

} catch (PDOException $e) {
    error_log('Materiel fiche error: ' . $e->getMessage());
    $materiel = [];
    $affectations = [];
    $historique = [];
    $message = "Erreur lors du chargement des données.";
    $message_type = "danger";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . ' - ' . htmlspecialchars($materiel['designation'] ?? ''); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . '/../../includes/header.php'; ?>


        <main class="col-12 col-md-10 offset-md-1">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-box-seam me-2"></i><?php echo htmlspecialchars($materiel['designation']); ?></h2>
                    <p class="mb-0 text-muted"><small>Code interne: <?php echo htmlspecialchars($materiel['code_interne']); ?></small></p>
                </div>
                <div>
                    <a href="modifier.php?id=<?php echo $id; ?>" class="btn btn-primary me-2"><i class="bi bi-pencil me-2"></i>Modifier</a>
                    <a href="codebarre.php?id=<?php echo $id; ?>" class="btn btn-success me-2"><i class="bi bi-barcode me-2"></i>Code-barres</a>
                    <a href="print_fiche.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary"><i class="bi bi-printer me-2"></i>Imprimer</a>
                    <a href="liste.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour</a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Informations principales -->
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-header bg-white"><i class="bi bi-info-circle me-2 text-primary"></i>Informations générales</div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Code interne</label>
                                <div class="col-sm-8"><?php echo htmlspecialchars($materiel['code_interne']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Désignation</label>
                                <div class="col-sm-8"><?php echo htmlspecialchars($materiel['designation']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Marque</label>
                                <div class="col-sm-8"><?php echo htmlspecialchars($materiel['marque'] ?? '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Modèle</label>
                                <div class="col-sm-8"><?php echo htmlspecialchars($materiel['modele'] ?? '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Numéro de série</label>
                                <div class="col-sm-8"><?php echo htmlspecialchars($materiel['numero_serie'] ?? '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Montant d'acquisition</label>
                                <div class="col-sm-8"><?php echo htmlspecialchars($materiel['provenance'] ?? '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label fw-medium">Observations</label>
                                <div class="col-sm-8"><?php echo nl2br(htmlspecialchars($materiel['observations'] ?? '-')); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Codes-barres -->
               

            <hr>

            <!-- Quantités -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Quantité totale</div>
                            <div class="display-6 fw-bold text-dark"><?php echo htmlspecialchars($materiel['quantite_totale']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Quantité disponible</div>
                            <div class="display-6 fw-bold <?php echo $materiel['quantite_disponible'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo htmlspecialchars($materiel['quantite_disponible']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Quantité affectée</div>
                            <div class="display-6 fw-bold text-info"><?php echo htmlspecialchars($materiel['quantite_affectee']); ?></div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Historique -->
            <div class="card">
                <div class="card-header bg-white">
                    <i class="bi bi-clock-history me-2 text-secondary"></i>Historique des mouvements
                </div>
                <div class="card-body p-0">
                    <?php if (empty($historique)): ?>
                        <p class="text-muted text-center py-4">Aucun mouvement enregistré pour ce matériel.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date/Heure</th>
                                        <th>Type</th>
                                        <th>Qté</th>
                                        <th>Service</th>
                                        <th>Utilisateur</th>
                                        <th>Observations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historique as $h): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($h['date_heure'])); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($h['evenement']); ?></span></td>
                                        <td><?php echo htmlspecialchars($h['quantite']); ?></td>
                                        <td><?php echo htmlspecialchars($h['service_nom'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($h['username'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($h['observations'] ?? '-'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>