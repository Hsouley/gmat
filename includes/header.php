<?php
// Définir le chemin de base relatif au projet
if (!isset($base_path)) {
    $script_path = $_SERVER['PHP_SELF'];
    if (strpos($script_path, '/modules/') !== false) {
        $base_path = '../../';
    } elseif (basename($script_path) === 'index.php') {
        $base_path = '';
    } else {
        $base_path = '../';
    }
}
?>
<!-- Header commun -->
<header class="bg-dark text-white py-2 shadow-sm">
  <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
      <span class="badge bg-primary">MFPTE</span>
      <h5 class="mb-0">Direction des Ressources Financi&egrave;res & Mat&eacute;rielles</h5>
    </div>
    <div class="d-flex align-items-center gap-3">
      <div class="small text-light">
        <i class="bi bi-person-circle me-1"></i>
        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Invit&eacute;'); ?>
        <span class="badge bg-secondary ms-1"><?php echo htmlspecialchars($_SESSION['role_nom'] ?? ''); ?></span>
      </div>
     
    </div>
  </div>
</header>

<!-- Sidebar -->
<nav class="bg-primary text-white min-vh-100 sidebar-nav shadow" style="width: 260px; min-height: calc(100vh - 60px);">
  <div class="p-3 border-bottom border-white-25">
    <h6 class="text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 2px; opacity: 0.7;">Menu principal</h6>
  </div>
  <ul class="nav flex-column py-2">
    <li class="nav-item"><a href="<?php echo $base_path; ?>index.php" class="nav-link text-white py-2"><i class="bi bi-speedometer2 me-2"></i>Tableau de bord</a></li>
    <li class="nav-item"><a href="<?php echo $base_path; ?>modules/materiels/liste.php" class="nav-link text-white py-2"><i class="bi bi-box me-2"></i>Mat&eacute;riels</a></li>
    <li class="nav-item"><a href="<?php echo $base_path; ?>modules/affectations/liste.php" class="nav-link text-white py-2"><i class="bi bi-share me-2"></i>Affectations</a></li>
    <li class="nav-item"><a href="<?php echo $base_path; ?>modules/retours/liste.php" class="nav-link text-white py-2"><i class="bi bi-arrow-return-left me-2"></i>Retours</a></li>
    
    <li class="nav-item"><hr class="border-white-25 my-2"></li>
    <li class="nav-item"><a href="<?php echo $base_path; ?>modules/categories/liste.php" class="nav-link text-white py-2"><i class="bi bi-tags me-2"></i>Cat&eacute;gories</a></li>
    <li class="nav-item"><a href="<?php echo $base_path; ?>modules/services/liste.php" class="nav-link text-white py-2"><i class="bi bi-building me-2"></i>Services</a></li>
    <?php if ($_SESSION['role_id'] == ROLE_ADMIN): ?>
    <?php endif; ?>
    <li class="nav-item"><hr class="border-white-25 my-2"></li>
    <li class="nav-item"><a href="<?php echo $base_path; ?>logout.php" class="nav-link text-white py-2"><i class="bi bi-box-arrow-left me-2"></i>D&eacute;connexion</a></li>
  </ul>
</nav>
