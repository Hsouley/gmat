<?php
/**
 * Script de création d'utilisateur administrateur
 * Usage: Accéder à http://localhost/gmat/creer_admin.php
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Création d'un utilisateur Administrateur</h2>";

try {
    $db = getDb();

    // Vérifier si l'utilisateur admin existe déjà
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute(['admin', 'admin@example.com']);

    if ($stmt->fetch()) {
        echo "<p style='color: orange;'>⚠️ L'utilisateur 'admin' existe déjà.</p>";
        echo "<p><strong>Identifiants par défaut :</strong></p>";
        echo "<ul><li>Nom d'utilisateur: <code>admin</code></li>";
        echo "<li>Email: <code>admin@example.com</code></li>";
        echo "<li>Mot de passe: <code>admin123</code></li></ul>";
        exit;
    }

    // Vérifier que le rôle Administrateur existe
    $stmt = $db->prepare("SELECT id FROM roles WHERE nom = 'Administrateur'");
    $stmt->execute();
    $role = $stmt->fetch();

    if (!$role) {
        throw new Exception("Le rôle 'Administrateur' n'existe pas dans la base de données.");
    }

    $role_id = $role['id'];

    // Hash du mot de passe
    $mot_de_passe = 'admin123';
    $password_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    // Insérer l'utilisateur admin
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password_hash, role_id, actif)
        VALUES (?, ?, ?, ?, 1)
    ");

    $stmt->execute([
        'admin',
        'admin@example.com',
        $password_hash,
        $role_id
    ]);

    echo "<p style='color: green;'>✅ <strong>Utilisateur administrateur créé avec succès !</strong></p>";
    echo "<p><strong>Identifiants :</strong></p>";
    echo "<ul><li>Nom d'utilisateur: <code>admin</code></li>";
    echo "<li>Email: <code>admin@example.com</code></li>";
    echo "<li>Mot de passe: <code>admin123</code></li></ul>";

    // Mettre à jour la date de connexion pour tester
    $updateStmt = $db->prepare("UPDATE users SET dernier_connexion = NOW() WHERE username = ?");
    $updateStmt->execute(['admin']);

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Erreur création admin: " . $e->getMessage());
}

echo "<p><small>⚠️ Supprimez ce fichier après utilisation.</small></p>";
?>
<br>
<a href="login.php">↩ Retour à la page de connexion</a>