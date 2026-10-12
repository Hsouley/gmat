<<<<<<< HEAD
# Application de Gestion des Ressources Matérielles (GMAT)

## Présentation

GMAT est une application web professionnelle développée en PHP natif pour la Direction des Ressources Matérielles d'une administration publique. Elle permet de gérer le patrimoine matériel (matériels, catégories, services, affectations, retours, historique et rapports).

## Technologies utilisées

- **PHP natif** (sans framework)
- **MySQL / MariaDB** (PDO, requêtes préparées)
- **HTML5, CSS3, JavaScript**
- **Bootstrap 5** (responsive design)
- **Bootstrap Icons**
- **Chart.js** (graphiques du tableau de bord)
- **TCPDF** (génération PDF - via Composer)
- **Barcode Generator** (codes-barres Code 128)

## Structure du projet

```
gmat/
├── config/
│   ├── config.php          # Configuration générale (sessions, constantes)
│   └── database.php        # Connexion PDO (singleton)
├── database/
│   └── schema.sql          # Script SQL complet (tables, vues, procédures)
├── modules/
│   ├── materiels/          # Liste, fiche, ajout, modification, code-barres, entrée
│   ├── categories/         # Liste, ajout, modification
│   ├── services/           # Liste, ajout, modification, voir
│   ├── affectations/       # Liste, ajout
│   ├── retours/            # Liste, ajout
│   ├── historique/         # Recherche et traçabilité
│   ├── rapports/           # Rapports (PDF, Excel)
│   └── utilisateurs/       # Gestion des utilisateurs (admin uniquement)
├── includes/
│   ├── header.php          # Menu de navigation latéral et barre supérieure
│   └── footer.php          # Pied de page
├── assets/
│   ├── css/                # Styles personnalisés
│   ├── js/                 # Scripts JavaScript (tableau de bord, etc.)
│   └── img/                # Images
├── vendor/                 # Dépendances Composer (TCPDF, etc.)
├── index.php               # Tableau de bord
├── login.php               # Page de connexion
├── logout.php              # Déconnexion sécurisée
└── README.md               # Ce document
```

## Installation sur XAMPP

### 1. Prérequis

- **XAMPP** (Apache + MySQL + PHP) installé
- **Composer** installé (pour les bibliothèques de code-barres et PDF)

### 2. Copie des fichiers

Placez le dossier `gmat/` dans le répertoire `htdocs/` de votre installation XAMPP :

```
C:\xampp\htdocs\gmat\
```

### 3. Création de la base de données

1. Démarrez le serveur **MySQL** depuis le panneau de contrôle XAMPP.
2. Ouvrez **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Importez le fichier `database/schema.sql` dans votre base de données.

Le script crée :
- La base `application_gestion_materielle`
- Toutes les tables (`users`, `roles`, `categories`, `services`, `materiels`, `entrees_stock`, `affectations`, `retours`, `mouvements`, `historique_materiels`)
- Les vues utiles (`v_etat_materiels`, `v_quantites_restock`)
- Les procédures stockées (transactions sécurisées)
- Les index et contraintes

### 4. Configuration de la base de données

Le fichier `config/database.php` utilise par défaut :
- **Serveur** : `localhost`
- **Base** : `application_gestion_materielle`
- **Utilisateur** : `root`
- **Mot de passe** : ` ` (vide)

Modifiez ces valeurs si nécessaire.

### 5. Installation des dépendances (Composer)

Ouvrez un terminal dans le répertoire du projet et exécutez :

```bash
composer install
```

### 6. Compte administrateur initial

Le compte administrateur par défaut est configuré dans la base de données :

- **Nom d'utilisateur** : `admin`
- **Mot de passe** : `admin123`

**Important** : Changez ce mot de passe immédiatement après la première connexion via le module de gestion des utilisateurs.

### 7. Accès à l'application

Ouvrez votre navigateur et accédez à :

```
http://localhost/gmat/login.php
```

## Fonctionnalités

### Authentification
- Connexion sécurisée avec identifiant/email et mot de passe
- Sessions protégées (cookie sécurisé, httpOnly, SameSite)
- Déconnexion sécurisée
- Deux rôles : **Administrateur** (tous droits) et **Gestionnaire** (matériels, affectations, rapports)

### Tableau de bord
- Indicateurs clés : catégories, services, matériels, quantités disponibles/affectées, ruptures de stock, affectations
- Graphiques : répartition par catégorie, par état, par service, dernières affectations
- Derniers mouvements et raccourcis rapides

### Gestion des catégories
- Création, modification, consultation, recherche, activation/désactivation
- Protection contre la suppression des catégories utilisées
- Vérification des doublons de noms

### Gestion des services
- Création, modification, consultation, recherche
- Affichage des matériels affectés au service

### Registre des matériels
- Fiche complète (code interne, désignation, catégorie, marque, modèle, série, quantités, état, date d'entrée, provenance)
- Génération automatique du code interne (format MAT-XXXXX)
- Génération du code-barres (format Code 128) associée au code interne
- Liste filtrable et paginée (recherche, catégorie, état, disponibilité)
- Impression et export du code-barres

### Entrées de stock
- Enregistrement d'une nouvelle entrée pour un matériel existant
- Mise à jour automatique des quantités disponibles et totales
- Traçabilité complète (historique et mouvements)
- Option de mise à jour de l'état du matériel si nécessaire

### Affectations
- Affectation d'une quantité à un service
- Vérification de la disponibilité (transaction sécurisée, verrouillage FOR UPDATE)
- Enregistrement du mouvement dans l'historique et mise à jour du stock
- Protection contre les affectations supérieures au stock disponible
- Possibilité de plusieurs affectations simultanées pour un même matériel vers différents services

### Retours
- Sélection de l'affectation à retourner
- Vérification que la quantité retournée ne dépasse pas la quantité affectée restante
- Enregistrement du retour (date, état du matériel, observations)
- Mise à jour du stock uniquement pour les unités aptes à être remises en stock
- Conservation de l'historique initial (l'affectation initiale n'est jamais supprimée ou modifiée)

### Historique et traçabilité
- Recherche par code interne, code-barres, désignation, numéro de série
- Fiche de traçabilité : informations générales, quantités, historique chronologique des opérations
- Événements : création, entrées, affectations, retours, modifications d'état, régularisations
- Bouton d'export et d'impression

### Rapports
- 8 rapports disponibles : état général, stock disponible, par catégorie, par service, historique des affectations, historique des mouvements, par état, indisponibles
- Filtres par période, catégorie, service, état
- Export en PDF (TCPDF) et Excel
- Documents professionnels (titre, date, critères de recherche, tableaux lisibles)

### Gestion des utilisateurs
- Création et modification des comptes (réservée à l'administrateur)
- Activation/désactivation des comptes
- Attribution des rôles
- Réinitialisation sécurisée du mot de passe (hashage obligatoire)
- Enregistrement de l'utilisateur responsable pour chaque opération

## Sécurité

- Requêtes préparées PDO (protection contre les injections SQL)
- Hachage des mots de passe (`password_hash`)
- Protection CSRF (tokens de session)
- Échappement des données (`htmlspecialchars`)
- Validation côté serveur pour toutes les entrées
- Vérification des autorisations côté serveur sur chaque page
- Sessions sécurisées (`httponly`, `SameSite=Strict`)
- Protection contre les doubles soumissions (transactions atomiques)
- Interdiction de suppression des matériels avec historique (désactivation contrôlée)
- Contrôle des quantités (jamais négatives, retours limités aux affectations restantes)

## Données de démonstration

Le script SQL (`database/schema.sql`) crée des catégories et services de démonstration. Pour créer des données de test :

1. Connectez-vous avec le compte administrateur (`admin` / `admin123`).
2. Ajoutez des catégories via le module **Catégories**.
3. Ajoutez des services via le module **Services**.
4. Créez des matériels via **Matériels**.
5. Enregistrez des entrées de stock.
6. Effectuez des affectations et des retours.

## Tests fonctionnels recommandés

1. **Connexion / Déconnexion**
2. **Tableau de bord** : vérifier la cohérence des indicateurs avec la base
3. **Catégories** : ajouter, modifier, désactiver, vérifier le blocage des doublons
4. **Services** : ajouter, modifier, consulter
5. **Matériels** : créer, modifier, consulter la fiche, générer et imprimer le code-barres
6. **Entrées de stock** : ajouter une entrée, vérifier l'augmentation des quantités disponibles
7. **Affectations** : affecter un matériel, vérifier la diminution du stock, tenter une affectation excessive (doit être bloquée)
8. **Retours** : retourner une affectation, vérifier l'augmentation du stock (si état bon), vérifier la conservation de l'historique
9. **Historique** : rechercher un matériel, consulter la fiche de traçabilité
10. **Rapports** : générer chaque rapport, vérifier le filtrage, exporter en PDF et Excel
11. **Utilisateurs** : créer, modifier, désactiver, changer le rôle (uniquement en tant qu'administrateur)

## Notes importantes

- L'application ne mélange pas l'état physique du matériel avec son statut d'affectation.
- Les opérations sur les quantités sont réalisées dans des transactions SQL (`FOR UPDATE`) pour éviter les incohérences et les modifications concurrentes.
- La suppression d'un matériel ayant un historique est interdite ; seule la désactivation est autorisée.
- Le code-barres est associé au code interne du matériel, permettant la recherche rapide par scan USB ou caméra téléphonique.

---

© 2025 — Direction des Ressources Matérielles — Application GMAT v1.0.0
=======
# gmat
GESTION DU MATERIEL 
>>>>>>> 02f424216e521de4c360560bab7aa2e67bff1fbf
