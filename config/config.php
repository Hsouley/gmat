<?php
/**
 * Configuration générale de l'application GMAT
 */
if (!defined('GMAT_APP')) {
    define('GMAT_APP', true);
}

// Constantes de l'application
if (!defined('APP_NAME')) define('APP_NAME', 'Direction des Ressources Matérielles Et Financières');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');
if (!defined('APP_URL')) define('APP_URL', 'http://localhost/gmat');

// Session
//session_set_cookie_params([
  //  'lifetime' => 3600 * 8, // 8 heures
   // 'path' => '/',
   // 'domain' => '',
   // 'secure' => false, // Mettre true en production avec HTTPS
   // 'httponly' => true,
   // 'samesite' => 'Strict'
//]);

// Initialiser la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la configuration de la base de données
require_once __DIR__ . '/database.php';

// Constantes des états de matériel
if (!defined('ETAT_MATERIEL_NEUF')) define('ETAT_MATERIEL_NEUF', 'Neuf');
if (!defined('ETAT_MATERIEL_TRES_BON')) define('ETAT_MATERIEL_TRES_BON', 'Très bon état');
if (!defined('ETAT_MATERIEL_BON')) define('ETAT_MATERIEL_BON', 'Bon état');
if (!defined('ETAT_MATERIEL_MOYEN')) define('ETAT_MATERIEL_MOYEN', 'État moyen');
if (!defined('ETAT_MATERIEL_MAUVAIS')) define('ETAT_MATERIEL_MAUVAIS', 'Mauvais état');
if (!defined('ETAT_MATERIEL_PANNE')) define('ETAT_MATERIEL_PANNE', 'En panne');

// Constantes des états d'affectation
if (!defined('STATUT_DISPO')) define('STATUT_DISPO', 'Disponible');
if (!defined('STATUT_AFFECTE')) define('STATUT_AFFECTE', 'Affecté');
if (!defined('STATUT_INDISPONIBLE')) define('STATUT_INDISPONIBLE', 'Indisponible');

// Constantes des rôles
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 1);
if (!defined('ROLE_GESTIONNAIRE')) define('ROLE_GESTIONNAIRE', 2);

// Constantes des types de mouvement
if (!defined('MVT_ENTREE')) define('MVT_ENTREE', 'entree');
if (!defined('MVT_AFFECTATION')) define('MVT_AFFECTATION', 'affectation');
if (!defined('MVT_RETOUR')) define('MVT_RETOUR', 'retour');
if (!defined('MVT_REGULARISATION')) define('MVT_REGULARISATION', 'reglare');

// ========== Expiration de l'application ==========
if (!defined('APP_EXPIRATION_JOURS')) define('APP_EXPIRATION_JOURS', 200);

function gmat_verifier_expiration(): void
{
    $fichier = __DIR__ . '/.install_date';
    $secret  = 'hima souley root';
    $dateFichier = null;
    $dateDb = null;

    // 1. Lecture de la date dans le fichier (signée pour détecter une modification)
    if (is_file($fichier)) {
        $contenu = trim((string) file_get_contents($fichier));
        $parts = explode('|', $contenu);
        if (count($parts) === 2 && hash_equals(hash_hmac('sha256', $parts[0], $secret), $parts[1])) {
            $dateFichier = (int) $parts[0];
        }
    }

    // 2. Lecture de la date dans la base de données
    try {
        $db = getDb();
        $db->exec("CREATE TABLE IF NOT EXISTS app_license (
            id INT PRIMARY KEY,
            install_ts BIGINT NOT NULL,
            signature VARCHAR(64) NOT NULL
        )");
        $row = $db->query("SELECT install_ts, signature FROM app_license WHERE id = 1")->fetch();
        if ($row && hash_equals(hash_hmac('sha256', (string) $row['install_ts'], $secret), $row['signature'])) {
            $dateDb = (int) $row['install_ts'];
        }
    } catch (Exception $e) {
        error_log('Licence: ' . $e->getMessage());
    }

    // 3. Première exécution : on enregistre la date partout
    $dates = array_filter([$dateFichier, $dateDb]);
    $install = $dates ? min($dates) : time();   // la plus ancienne date l'emporte

    if ($dateFichier === null) {
        $sig = hash_hmac('sha256', (string) $install, $secret);
        @file_put_contents($fichier, $install . '|' . $sig);
    }
    if ($dateDb === null) {
        try {
            $sig = hash_hmac('sha256', (string) $install, $secret);
            $st = getDb()->prepare("REPLACE INTO app_license (id, install_ts, signature) VALUES (1, ?, ?)");
            $st->execute([$install, $sig]);
        } catch (Exception $e) {}
    }

    // 4. Vérification
    $joursEcoules = (time() - $install) / 86400;
    if ($joursEcoules > APP_EXPIRATION_JOURS) {
        http_response_code(403);
        die('<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Licence expirée</title></head>
        <body style="font-family:Arial;text-align:center;margin-top:15%">
        <h2>Licence expirée</h2>
        <p>Votre licence de l\'application GMAT a expiré. Veuillez contacter l\'administrateur pour la renouveler.</p>
      
        </body></html>');
    }
}

gmat_verifier_expiration();