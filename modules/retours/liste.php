<?php
/** Liste des retours - Module GMAT */
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

$page_title = 'Gestion des retours';
$page_id = 'retours';

try {
    $db = getDb();

    // Pagination
    $limit = 20;
    $page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Récupérer les services pour le filtre
    $services = $db->query("SELECT id, nom, code FROM services WHERE actif = 1 ORDER BY nom")->fetchAll();

    // Construire la requête
    $where = "WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $where .= " AND (m.code_interne LIKE ? OR m.designation LIKE ? OR s.nom LIKE ? OR s.code LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Compter le total
    $countQuery = $db->prepare("
        SELECT COUNT(*) FROM retours r
        JOIN materiels m ON m.id = r.materiel_id
        JOIN services s ON s.id = (SELECT service_id FROM affectations WHERE id = r.affectation_id LIMIT 1)
        $where
    ");
    $countQuery->execute($params);
    $total = $countQuery->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    // Récupérer les retours
    $paramsLimit = array_merge($params, [$limit, $offset]);
    $query = $db->prepare("
        SELECT r.*, m.code_interne, m.designation, s.nom as service_nom, s.code as service_code, u.username
        FROM retours r
        JOIN materiels m ON m.id = r.materiel_id
        JOIN affectations a ON a.id = r.affectation_id
        JOIN services s ON s.id = a.service_id
        LEFT JOIN users u ON u.id = r.utilisateur_id
        $where
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $query->execute($paramsLimit);
    $retours = $query->fetchAll();

} catch (Exception $e) {
    error_log('Retours list error: ' . $e->getMessage());
    $retours = [];
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


    <main class="main-content" style="margin-left: 260px; padding: 2rem 2rem 3rem 2rem; max-width: 1200px;">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-arrow-return-left me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <a href="ajouter.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Enregistrer un retour</a>
            </div>

            <?php if (isset($message)): ?>
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
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['code'] . ' - ' . $s['nom']); ?></option>
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
                    </form>
                </div>
            </div>

            <!-- Tableau -->
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($retours)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-arrow-return-left fs-1 text-muted opacity-50 mb-3"></i>
                            <p class="text-muted">Aucun retour trouv<?php echo $search ? 'e correspondant' : 'e'; ?>.</p>
                            <a href="ajouter.php" class="btn btn-outline-primary mt-3">Enregistrer un retour</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Matériel</th>
                                        <th>Service</th>
                                        <th class="text-center">Qté retournée</th>
                                        <th>État au retour</th>
                                        <th>Date</th>
                                        <th>Utilisateur</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($retours as $r): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($r['code_interne']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($r['designation']); ?></td>
                                        <td><?php echo htmlspecialchars($r['service_nom'] . ' (' . $r['service_code'] . ')'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($r['quantite_retournee']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch ($r['etat_retour']) {
                                                    case 'Neuf': echo 'success'; break;
                                                    case 'Très bon état': echo 'info'; break;
                                                    case 'Bon état': echo 'primary'; break;
                                                    case 'État moyen': echo 'warning text-dark'; break;
                                                    case 'Mauvais état': echo 'danger'; break;
                                                    case 'En panne': echo 'dark'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($r['etat_retour']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($r['date_retour'])); ?></td>
                                        <td><?php echo htmlspecialchars($r['username'] ?? '-'); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="voir.php?id=<?php echo $r['id']; ?>" class="btn btn-outline-primary" title="Voir">
                                                    <i class="bi bi-eye"></i>
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
                <?php if (!empty($retours)): ?>
                <div class="card-footer bg-white">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php
                            $queryString = '';
                            if ($search) $queryString .= '&search=' . urlencode($search);
                            for ($p = 1; $p <= $totalPages; $p++):
                            ?>
                                <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="liste.php?page=<?php echo $p . $queryString; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                        <p class="text-muted mb-0 mt-2 small"><?php echo $total; ?> retour<?php echo $total > 1 ? 's' : ''; ?> au total</p>
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
</script>
</body>
</html>