<?php
/**
 * Configuration de la base de données
 * Application de gestion des ressources matérielles (GMAT)
 */

// Empêche l'accès direct
if (!defined('GMAT_APP')) {
    die('Accès interdit');
}

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'application_gestion_materielle');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Connexion PDO à la base de données
 * Utilise un singleton pour une seule instance de connexion
 */
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            // Only add EMULATE_PREPARES if the constant exists (PHP >= 5.1.3)
            if (defined('PDO::ATTR_EMULATE_PREPARES')) {
                $options[PDO::ATTR_EMULATE_PREPARES] = false;
            }

            $this->pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                DB_USER,
                DB_PASS,
                $options
            );
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('Impossible de se connecter à la base de données. Veuillez vérifier la configuration.');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getPdo() {
        return $this->pdo;
    }

    // Empêcher le clonage de l'objet
    private function __clone() { }

    // Empêcher la désérialisation
    public function __wakeup() { }
}

function getDb() {
    return Database::getInstance()->getPdo();
}