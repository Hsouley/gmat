<?php
/** Liste des matériels - Module GMAT */
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

$page_title = 'Gestion des matériels';
$page_id = 'materiels';

$message = '';
$message_type = '';

// Pagination et recherche
$limit = 20;
$page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categorie_id = isset($_GET['categorie_id']) ? intval($_GET['categorie_id']) : 0;
$etat = isset($_GET['etat']) ? trim($_GET['etat']) : '';
$disponibilite = isset($_GET['disponibilite']) ? trim($_GET['disponibilite']) : '';

try {
    $db = getDb();

    // Récupérer les catégories pour le filtre
    $categories = $db->query("SELECT id, nom FROM categories WHERE actif = 1 ORDER BY nom")->fetchAll();

    // Construire la requête
    $where = "WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $where .= " AND (m.code_interne LIKE ? OR m.designation LIKE ? OR m.code_barre LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($categorie_id > 0) {
        $where .= " AND m.categorie_id = ?";
        $params[] = $categorie_id;
    }

    if (!empty($etat)) {
        $where .= " AND m.etat = ?";
        $params[] = $etat;
    }

    if (!empty($disponibilite)) {
        if ($disponibilite === 'disponible') {
            $where .= " AND m.quantite_disponible > 0";
        } elseif ($disponibilite === 'rupture') {
            $where .= " AND m.quantite_disponible <= 0";
        } elseif ($disponibilite === 'affecte') {
            $where .= " AND m.quantite_affectee > 0";
        }
    }

    // Compter le total
    $countQuery = $db->prepare("SELECT COUNT(*) FROM materiels m $where");
    $countQuery->execute($params);
    $total = $countQuery->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    // Récupérer les matériels
    $paramsLimit = array_merge($params, [$limit, $offset]);
    $query = $db->prepare("
        SELECT m.*, c.nom as categorie_nom
        FROM materiels m
        LEFT JOIN categories c ON m.categorie_id = c.id
        $where
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $query->execute($paramsLimit);
    $materiels = $query->fetchAll();

    // États pour le filtre
    $etats = ['Neuf', 'Très bon état', 'Bon état', 'État moyen', 'Mauvais état', 'En panne'];

} catch (Exception $e) {
    error_log('Materiels list error: ' . $e->getMessage());
    $materiels = [];
    $categories = [];
    $total = 0;
    $totalPages = 1;
    $etats = [];
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
                <h2><i class="bi bi-box me-2"></i><?php echo htmlspecialchars($page_title); ?></h2>
                <div>
                    <a href="ajouter.php" class="btn btn-primary me-2"><i class="bi bi-plus-circle me-2"></i>Nouveau matériel</a>
                    <a href="entree.php" class="btn btn-success"><i class="bi bi-plus-square me-2"></i>Nouvelle entrée</a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-header bg-white"><i class="bi bi-funnel me-2"></i>Filtres et recherche</div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Recherche</label>
                            <input type="text" class="form-control" name="search" placeholder="Code, désignation, code-barres..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Catégorie</label>
                            <select class="form-select" name="categorie_id">
                                <option value="">Toutes</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $categorie_id == $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">État</label>
                            <select class="form-select" name="etat">
                                <option value="">Tous</option>
                                <?php foreach ($etats as $e): ?>
                                    <option value="<?php echo $e; ?>" <?php echo $etat === $e ? 'selected' : ''; ?>>
                                        <?php echo $e; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Disponibilité</label>
                            <select class="form-select" name="disponibilite">
                                <option value="">Toutes</option>
                                <option value="disponible" <?php echo $disponibilite === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                <option value="rupture" <?php echo $disponibilite === 'rupture' ? 'selected' : ''; ?>>Rupture</option>
                                <option value="affecte" <?php echo $disponibilite === 'affecte' ? 'selected' : ''; ?>>Affecté</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Filtrer</button>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <a href="liste.php" class="btn btn-outline-secondary w-100" title="Réinitialiser"><i class="bi bi-x-circle"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tableau -->
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($materiels)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-box-seam fs-1 text-muted opacity-50 mb-3"></i>
                            <p class="text-muted">Aucun matériel trouv<?php echo $search || $categorie_id || $etat || $disponibilite ? 'e correspondant aux critères' : ''; ?>.</p>
                            <a href="ajouter.php" class="btn btn-outline-primary mt-3">Ajouter un premier matériel</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code interne</th>
                                        <th>Code-barres</th>
                                        <th>Désignation</th>
                                        <th>Catégorie</th>
                                        <th>État</th>
                                        <th class="text-center">Qté totale</th>
                                        <th class="text-center">Qté dispo.</th>
                                        <th class="text-center">Qté affectée</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materiels as $m): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($m['code_interne']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($m['code_barre'] ?? '-'); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($m['designation']); ?></strong>
                                            <?php if ($m['marque'] || $m['modele']): ?>
                                                <br><small class="text-muted">
                                                    <?php echo htmlspecialchars($m['marque'] ?? ''); ?>
                                                    <?php echo htmlspecialchars($m['modele'] ?? ''); ?>
                                                    <?php if ($m['numero_serie']): ?> (<?php echo htmlspecialchars($m['numero_serie']); ?>)<?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($m['categorie_nom'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch ($m['etat']) {
                                                    case 'Neuf': echo 'success'; break;
                                                    case 'Très bon état': echo 'info'; break;
                                                    case 'Bon état': echo 'primary'; break;
                                                    case 'État moyen': echo 'warning text-dark'; break;
                                                    case 'Mauvais état': echo 'danger'; break;
                                                    case 'En panne': echo 'dark'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($m['etat']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?php echo htmlspecialchars($m['quantite_totale']); ?></td>
                                        <td class="text-center">
                                            <span class="<?php echo $m['quantite_disponible'] > 0 ? 'text-success fw-bold' : 'text-danger fw-bold'; ?>">
                                                <?php echo htmlspecialchars($m['quantite_disponible']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?php echo htmlspecialchars($m['quantite_affectee']); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="fiche.php?id=<?php echo $m['id']; ?>" class="btn btn-outline-primary" title="Voir la fiche">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="modifier.php?id=<?php echo $m['id']; ?>" class="btn btn-outline-secondary" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="entree.php?materiel_id=<?php echo $m['id']; ?>" class="btn btn-outline-success" title="Nouvelle entrée">
                                                    <i class="bi bi-plus-circle"></i>
                                                </a>
                                                <a href="../affectations/ajouter.php?materiel_id=<?php echo $m['id']; ?>" class="btn btn-outline-info" title="Affecter">
                                                    <i class="bi bi-share"></i>
                                                </a>
                                                <a href="codebarre.php?id=<?php echo $m['id']; ?>" class="btn btn-outline-dark" title="Code-barres">
                                                    <i class="bi bi-barcode"></i>
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
                <?php if (!empty($materiels)): ?>
                <div class="card-footer bg-white">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php
                            $queryString = '';
                            if ($search) $queryString .= '&search=' . urlencode($search);
                            if ($categorie_id) $queryString .= '&categorie_id=' . $categorie_id;
                            if ($etat) $queryString .= '&etat=' . urlencode($etat);
                            if ($disponibilite) $queryString .= '&disponibilite=' . urlencode($disponibilite);
                            for ($p = 1; $p <= $totalPages; $p++):
                            ?>
                                <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="liste.php?page=<?php echo $p . $queryString; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                        <p class="text-muted mb-0 mt-2 small text-center">
                            <?php echo $total; ?> matériel<?php echo $total > 1 ? 's' : ''; ?> au total
                        </p>
                    </nav>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>