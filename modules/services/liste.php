<?php
/** Liste des services - Module GMAT */
session_start();
require_once __DIR__ . '/../../config/config.php';

// Protection des pages
if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
    header('Location: ../login.php?error=non_autorise');
    exit;
}

$allowed_roles = [ROLE_ADMIN, ROLE_GESTIONNAIRE];
if (!in_array($_SESSION['role_id'], $allowed_roles)) {
    header('Location: ../login.php?error=non_autorise');
    exit;
}

$page_title = 'Gestion des services';
$page_id = 'services';

$message = '';
$message_type = '';

// Pagination et recherche
$limit = 20;
$page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $db = getDb();

    // Construire la requête
    $where = "WHERE actif = 1";
    $params = [];

    if (!empty($search)) {
        $where .= " AND (nom LIKE ? OR code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Compter le total
    $countQuery = $db->prepare("SELECT COUNT(*) FROM services $where");
    $countQuery->execute($params);
    $total = $countQuery->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    // Récupérer les services
    $paramsLimit = array_merge($params, [$limit, $offset]);
    $query = $db->prepare("SELECT * FROM services $where ORDER BY nom LIMIT ? OFFSET ?");
    $query->execute($paramsLimit);
    $services = $query->fetchAll();

} catch (PDOException $e) {
    error_log('Services list error: ' . $e->getMessage());
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
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . '/../../includes/header.php'; ?>

<main class="main-content" style="margin-left: 260px; padding: 2rem 2rem 3rem 2rem; max-width: 1200px;">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-building me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <a href="ajouter.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nouveau service</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                    <i class="bi bi-info-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-white">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un service..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary" type="button" id="searchBtn"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($services)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-building fs-1 text-muted opacity-50 mb-3"></i>
                            <p class="text-muted">Aucun service trouv<?php echo $search ? 'e correspondant' : 'e'; ?>.</p>
                            <a href="ajouter.php" class="btn btn-outline-primary mt-3">Ajouter un service</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Nom</th>
                                        <th>Responsable</th>
                                        <th>Localisation</th>
                                        <th>Statut</th>
                                        <th>Matériels</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $s): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($s['code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($s['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($s['responsable'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($s['localisation'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $s['actif'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $s['actif'] ? 'Actif' : 'Inactif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            try {
                                                $db = getDb();
                                                $stmt = $db->prepare("SELECT COUNT(*) FROM affectations WHERE service_id = ?");
                                                $stmt->execute([$s['id']]);
                                                $affectation_count = $stmt->fetchColumn();
                                                echo '<span class="badge bg-info">' . $affectation_count . '</span>';
                                            } catch (Exception $e) {}
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="voir.php?id=<?php echo $s['id']; ?>" class="btn btn-outline-primary" title="Voir les matériels">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="modifier.php?id=<?php echo $s['id']; ?>" class="btn btn-outline-primary" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
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
                <?php if (!empty($services)): ?>
                <div class="card-footer bg-white">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="liste.php?page=<?php echo $p; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $p; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                        <p class="text-muted mb-0 mt-2 small">
                            <?php echo $total; ?> service<?php echo $total > 1 ? 's' : ''; ?> au total
                        </p>
                    </nav>
                </div>
                <?php endif; ?>
            </div>

        </main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
document.getElementById('searchBtn').addEventListener('click', function() {
    const search = document.getElementById('searchInput').value.trim();
    window.location.href = 'liste.php?search=' + encodeURIComponent(search);
});
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('searchBtn').click();
    }
});
</script>
</body>
</html>