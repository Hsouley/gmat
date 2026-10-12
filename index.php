<?php
/**
 * Page d'accueil - Tableau de bord
 */

require_once __DIR__ . '/config/config.php';

// Protection des pages - seul l'utilisateur authentifié peut accéder
if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
    header('Location: login.php?error=non_autorise');
    exit;
}

// Autorisation des rôles
$allowed_roles = [ROLE_ADMIN, ROLE_GESTIONNAIRE];
if (!in_array($_SESSION['role_id'], $allowed_roles)) {
    header('Location: login.php?error=non_autorise');
    exit;
}

$page_title = 'Tableau de bord';
$page_id = 'dashboard';

// Fonctions de base de données
function getDashboardStats() {
    try {
        $db = getDb();
        $stats = [];

        // Nombre total de cat&eacute;gories
        $stmt = $db->query('SELECT COUNT(*) as total FROM categories WHERE actif = 1');
        $stats['categories'] = $stmt->fetchColumn();

        // Nombre total de services
        $stmt = $db->query('SELECT COUNT(*) as total FROM services WHERE actif = 1');
        $stats['services'] = $stmt->fetchColumn();

        // Nombre total de mat&eacute;riels (r&eacute;f&eacute;rences)
        $stmt = $db->query('SELECT COUNT(DISTINCT id) as total FROM materiels');
        $stats['materiels'] = $stmt->fetchColumn();

        // Quantit&eacute; totale de mat&eacute;riels (unit&eacute;s)
        $stmt = $db->query('SELECT SUM(quantite_totale) as total FROM materiels');
        $stats['quantite_totale'] = $stmt->fetchColumn() ?: 0;

        // Quantit&eacute; totale disponible
        $stmt = $db->query('SELECT SUM(quantite_disponible) as total FROM materiels');
        $stats['quantite_disponible'] = $stmt->fetchColumn() ?: 0;

        // Quantit&eacute; totale affect&eacute;e
        $stmt = $db->query('SELECT SUM(quantite_affectee) as total FROM materiels');
        $stats['quantite_affectee'] = $stmt->fetchColumn() ?: 0;

        // Nombre de mat&eacute;riels en rupture de stock
        $stmt = $db->query('SELECT COUNT(*) as total FROM materiels WHERE quantite_disponible <= 0');
        $stats['rupture'] = $stmt->fetchColumn();

        // Nombre d'affectations
        $stmt = $db->query('SELECT COUNT(*) as total FROM affectations');
        $stats['affectations'] = $stmt->fetchColumn();

        // R&eacute;partition par cat&eacute;gorie
        $stmt = $db->query('
            SELECT c.nom as categorie, COUNT(m.id) as nb, COALESCE(SUM(m.quantite_totale),0) as quantite
            FROM categories c
            LEFT JOIN materiels m ON m.categorie_id = c.id
            WHERE c.actif = 1
            GROUP BY c.id, c.nom
            ORDER BY nb DESC
        ');
        $stats['par_categorie'] = $stmt->fetchAll();

        // R&eacute;partition par &eacute;tat
        $stmt = $db->query('
            SELECT etat, COUNT(id) as nb, COALESCE(SUM(quantite_totale),0) as quantite
            FROM materiels
            GROUP BY etat
            ORDER BY quantite DESC
        ');
        $stats['par_etat'] = $stmt->fetchAll();

        // Quantit&eacute;s affect&eacute;es par service
        $stmt = $db->query('
            SELECT s.nom, SUM(a.quantite_affectee) as total
            FROM services s
            LEFT JOIN affectations a ON a.service_id = s.id
            WHERE s.actif = 1
            GROUP BY s.id, s.nom
            ORDER BY total DESC
        ');
        $stats['par_service'] = $stmt->fetchAll();

        // Derni&egrave;res affectations
        $stmt = $db->query('
            SELECT a.*, m.designation, m.code_interne, s.nom as service_nom
            FROM affectations a
            JOIN materiels m ON m.id = a.materiel_id
            JOIN services s ON s.id = a.service_id
            ORDER BY a.created_at DESC
            LIMIT 10
        ');
        $stats['dernieres_affectations'] = $stmt->fetchAll();

        // Derniers mouvements
        $stmt = $db->query('
            SELECT h.*, m.designation, m.code_interne, u.username
            FROM historique_materiels h
            JOIN materiels m ON m.id = h.materiel_id
            LEFT JOIN users u ON u.id = h.utilisateur_id
            ORDER BY h.date_heure DESC
            LIMIT 10
        ');
        $stats['derniers_mouvements'] = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Dashboard error: ' . $e->getMessage());
        $stats = [];
    }

    return $stats;
}

$stats = getDashboardStats();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/header.php'; ?>


    <main class="main-content" style="margin-left: 260px; padding: 2rem 2rem 3rem 2rem; max-width: 1200px;">

            <!-- KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="kpi-card bg-primary">
                        <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
                        <div class="kpi-value"><?php echo htmlspecialchars($stats['categories'] ?? 0); ?></div>
                        <div class="kpi-label">Cat&eacute;gories</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card bg-success">
                        <div class="kpi-icon"><i class="bi bi-building"></i></div>
                        <div class="kpi-value"><?php echo htmlspecialchars($stats['services'] ?? 0); ?></div>
                        <div class="kpi-label">Services</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card bg-info">
                        <div class="kpi-icon"><i class="bi bi-check2-square"></i></div>
                        <div class="kpi-value"><?php echo htmlspecialchars($stats['materiels'] ?? 0); ?></div>
                        <div class="kpi-label">R&eacute;f&eacute;rences mat&eacute;riel</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card bg-warning text-dark">
                        <div class="kpi-icon"><i class="bi bi-people"></i></div>
                        <div class="kpi-value"><?php echo htmlspecialchars($stats['affectations'] ?? 0); ?></div>
                        <div class="kpi-label">Affectations</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="kpi-card bg-success">
                        <div class="kpi-icon"><i class="bi bi-check2-square"></i></div>
                        <div class="kpi-value"><?php echo htmlspecialchars($stats['quantite_disponible'] ?? 0); ?></div>
                        <div class="kpi-label">Quantit&eacute; disponible</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card bg-info">
                        <div class="kpi-icon"><i class="bi bi-arrow-right-circle"></i></div>
                        <div class="kpi-value"><?php echo htmlspecialchars($stats['quantite_affectee'] ?? 0); ?></div>
                        <div class="kpi-label">Quantit&eacute; affect&eacute;e</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card bg-danger">
                        <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
                        <div class="kpi-value"><?php echo htmlspecialchars($stats['rupture'] ?? 0); ?></div>
                        <div class="kpi-label">En rupture de stock</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card bg-secondary">
                        <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
                        <div class="kpi-value"><?php echo htmlspecialchars($stats['quantite_totale'] ?? 0); ?></div>
                        <div class="kpi-label">Quantit&eacute; totale (unit&eacute;s)</div>
                    </div>
                </div>
            </div>


            <!-- Shortcuts -->
            <div class="card">
                <div class="card-header bg-white">
                    <i class="bi bi-stars me-2 text-warning"></i>Raccourcis
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="<?php echo $base_path ?? ""; ?>modules/materiels/liste.php" class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                <i class="bi bi-search fs-4 text-primary me-3"></i>
                                <div><strong>Consulter les mat&eacute;riels</strong><br><small class="text-muted">Recherche rapide</small></div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo $base_path ?? ""; ?>modules/categories/ajouter.php" class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                <i class="bi bi-plus-circle fs-4 text-success me-3"></i>
                                <div><strong>Categorie</strong><br><small class="text-muted">Nouvelle catégorie</small></div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo $base_path ?? ""; ?>modules/affectations/ajouter.php" class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                <i class="bi bi-share fs-4 text-info me-3"></i>
                                <div><strong>Nouvelle affectation</strong><br><small class="text-muted">Affecter aux services</small></div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo $base_path ?? ""; ?>modules/retours/ajouter.php" class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                <i class="bi bi-arrow-return-left fs-4 text-warning me-3"></i>
                                <div><strong>Retour mat&eacute;riel</strong><br><small class="text-muted">R&eacute;int&eacute;grer au stock</small></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/dashboard.js"></script>
</body>
</html>