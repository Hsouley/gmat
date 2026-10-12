<?php
/** Liste des affectations - Module GMAT */
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

$page_title = 'Gestion des affectations';
$page_id = 'affectations';

$message = '';
$message_type = '';

try {
    $db = getDb();

    // Pagination
    $limit = 20;
    $page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

    // Récupérer les services pour le filtre
    $services = $db->query("SELECT id, nom, code FROM services WHERE actif = 1 ORDER BY nom")->fetchAll();

    // Construire la requête - les affectations valides sont celles qui n'ont pas eu de retour total
    $where = "WHERE a.quantite_affectee > 0";
    $params = [];

    if (!empty($search)) {
        $where .= " AND (m.code_interne LIKE ? OR m.designation LIKE ? OR s.nom LIKE ? OR s.code LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if ($service_id > 0) {
        $where .= " AND a.service_id = ?";
        $params[] = $service_id;
    }

    // Compter le total
    $countQuery = $db->prepare("
        SELECT COUNT(*) FROM affectations a
        JOIN materiels m ON m.id = a.materiel_id
        JOIN services s ON s.id = a.service_id
        $where
    ");
    $countQuery->execute($params);
    $total = $countQuery->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    // Récupérer les affectations
    $paramsLimit = array_merge($params, [$limit, $offset]);
    $query = $db->prepare("
        SELECT a.*, m.code_interne, m.designation, m.etat as etat_materiel, m.quantite_disponible, m.quantite_affectee as quantite_affectee_totale,
               s.nom as service_nom, s.code as service_code, u.username
        FROM affectations a
        JOIN materiels m ON m.id = a.materiel_id
        JOIN services s ON s.id = a.service_id
        LEFT JOIN users u ON u.id = a.utilisateur_id
        $where
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $query->execute($paramsLimit);
    $affectations = $query->fetchAll();

} catch (Exception $e) {
    error_log('Affectations list error: ' . $e->getMessage());
    $affectations = [];
    $services = [];
    $total = 0;
    $totalPages = 1;
    $message = "Erreur lors du chargement des données.";
    $message_type = "danger";
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
        <main class="main-content" style="margin-left: 260px; padding: 2rem 2rem 3rem 2rem; max-width: 1200px;">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-share me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <a href="ajouter.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nouvelle affectation</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchInput" placeholder="Rechercher (code, matériel, service)..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary" type="button" id="searchBtn"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-0">
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Service</label>
                            <select class="form-select" name="service_id">
                                <option value="">Tous les services</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo $service_id == $s['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['code'] . ' - ' . $s['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-2"></i>Filtrer</button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">&nbsp;</label>
                            <a href="liste.php" class="btn btn-outline-secondary w-100"><i class="bi bi-x-circle"></i> Réinitialiser</a>
                        </div>
                        <div class="col-md-3">
    <label class="form-label fw-medium">&nbsp;</label>
    <button type="button" class="btn btn-success w-100" id="printServiceBtn">
        <i class="bi bi-printer me-2"></i>Imprimer le matériel du service
    </button>
</div>
                    </form>
                </div>
            </div>

            <!-- Tableau -->
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($affectations)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-share fs-1 text-muted opacity-50 mb-3"></i>
                            <p class="text-muted">Aucune affectation trouv<?php echo $search || $service_id ? 'e correspondant' : 'e'; ?>.</p>
                            <a href="ajouter.php" class="btn btn-outline-primary mt-3">Créer une affectation</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Matériel</th>
                                        <th>Service</th>
                                        <th class="text-center">Qté</th>
                                        <th class="text-center">Disponible</th>
                                        <th class="text-center">État</th>
                                        <th>Date</th>
                                        <th>Utilisateur</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($affectations as $a): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($a['code_interne']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($a['designation']); ?></td>
                                        <td><?php echo htmlspecialchars($a['service_nom'] . ' (' . $a['service_code'] . ')'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($a['quantite_affectee']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($a['quantite_disponible']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch ($a['etat_materiel']) {
                                                    case 'Neuf': echo 'success'; break;
                                                    case 'Très bon état': echo 'info'; break;
                                                    case 'Bon état': echo 'primary'; break;
                                                    case 'État moyen': echo 'warning text-dark'; break;
                                                    case 'Mauvais état': echo 'danger'; break;
                                                    case 'En panne': echo 'dark'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($a['etat_materiel']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($a['date_affectation'])); ?></td>
                                        <td><?php echo htmlspecialchars($a['username'] ?? '-'); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="voir.php?id=<?php echo $a['id']; ?>" class="btn btn-outline-primary" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="retour.php?id=<?php echo $a['id']; ?>" class="btn btn-outline-success" title="Retourner">
                                                    <i class="bi bi-arrow-return-left"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($affectations)): ?>
                <div class="card-footer bg-white">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php
                            $queryString = '';
                            if ($search) $queryString .= '&search=' . urlencode($search);
                            if ($service_id) $queryString .= '&service_id=' . $service_id;
                            for ($p = 1; $p <= $totalPages; $p++):
                            ?>
                                <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="liste.php?page=<?php echo $p . $queryString; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                        <p class="text-muted mb-0 mt-2 small"><?php echo $total; ?> affectation<?php echo $total > 1 ? 's' : ''; ?> au total</p>
                    </nav>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
document.getElementById('searchBtn').addEventListener('click', function() {
    const search = document.getElementById('searchInput').value.trim();
    window.location.href = 'liste.php?search=' + encodeURIComponent(search);
});
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') document.getElementById('searchBtn').click();
});

document.getElementById('printServiceBtn').addEventListener('click', function() {
    const serviceId = document.querySelector('select[name="service_id"]').value;
    if (!serviceId) {
        alert('Veuillez d\'abord sélectionner un service dans la liste.');
        return;
    }
    window.open('imprimer_service.php?service_id=' + encodeURIComponent(serviceId), '_blank');
});
</script>
</body>
</html>