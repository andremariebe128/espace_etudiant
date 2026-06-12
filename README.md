# Plateforme Espace Etudiant Securisee

Mini plateforme de gestion des etudiants developpee en PHP, MySQL et PDO pour un environnement local XAMPP. Le projet couvre l'inscription, la connexion, la gestion du profil, les publications, les commentaires, les photos de profil, la recherche, la pagination, le theme clair/sombre et un tableau de bord statistique.

## Installation avec XAMPP

1. Copier le dossier du projet dans `C:\xampp\htdocs\plateforme_espace_etudiant`.
2. Demarrer Apache et MySQL depuis le panneau XAMPP.
3. Ouvrir phpMyAdmin : `http://localhost/phpmyadmin`.
4. Importer le fichier `database.sql`.
5. Ouvrir l'application : `http://localhost/plateforme_espace_etudiant/`.

La configuration MySQL utilise les valeurs XAMPP par defaut dans `db.php` :

- hote : `localhost`
- base : `espace_etudiant`
- utilisateur : `root`
- mot de passe : vide

## Fonctionnalites

- Inscription avec nom, prenom, email unique, mot de passe et confirmation.
- Connexion securisee avec `password_verify()`.
- Deconnexion avec destruction de session.
- Tableau de bord avec informations personnelles et statistiques.
- Modification du profil : nom, prenom, email et photo.
- Modification du mot de passe avec verification de l'ancien mot de passe.
- Suppression du compte avec confirmation textuelle.
- CRUD des publications.
- Consultation des publications personnelles et des publications des autres etudiants.
- Recherche de publications par titre, contenu, nom ou prenom d'auteur.
- Pagination des publications.
- Commentaires sur les publications.
- Suppression d'un commentaire par son auteur ou par le proprietaire de la publication.
- Theme clair/sombre persistant.

## Choix techniques realises

- PHP natif sans framework pour rester conforme aux objectifs pedagogiques.
- PDO pour la connexion MySQL et toutes les requetes SQL.
- Fichiers simples par action : inscription, connexion, profil, publications, commentaires.
- Fonctions communes centralisees dans `functions.php` pour eviter de dupliquer la securite.
- Interface HTML/CSS responsive sans bibliotheque externe.
- Photos stockees dans `uploads/profiles/` et noms de fichiers generes par le serveur.

## Mesures de securite mises en place

- Mots de passe stockes avec `password_hash()` et verifies avec `password_verify()`.
- Requetes SQL executees avec `prepare()` et `execute()`.
- Donnees utilisateur affichees avec `htmlentities()` via la fonction `e()`.
- Pages privees protegees par `require_login()`.
- Actions sensibles protegees par un token CSRF.
- Regeneration de l'identifiant de session avec `session_regenerate_id(true)` apres connexion.
- Destruction complete de la session a la deconnexion et apres suppression du compte.
- Verification de propriete avant modification ou suppression d'une publication.
- Verification de propriete avant suppression d'un commentaire.
- Upload photo limite aux formats JPG, PNG et WebP.
- Taille maximale de photo : 2 Mo.
- Nom final des fichiers uploades genere par le serveur.
- Blocage de l'execution de scripts PHP dans le dossier `uploads/` via `.htaccess`.

## Validations effectuees

- Champs obligatoires controles avec `isset()` et `empty()`.
- Email valide avec `filter_var(..., FILTER_VALIDATE_EMAIL)`.
- Email unique a l'inscription et lors de la modification du profil.
- Correspondance entre mot de passe et confirmation.
- Longueur minimale du mot de passe : 8 caracteres.
- Ancien mot de passe obligatoire et verifie avant changement.
- Titre de publication obligatoire et limite a 150 caracteres.
- Contenu de publication obligatoire.
- Commentaire obligatoire.
- Identifiants de ressources convertis en entiers et verifies en base.
- Tentatives d'acces direct a une ressource inexistante redirigees avec message d'erreur.

## Structure de la base de donnees

### Table `etudiants`

| Champ | Type | Role |
| --- | --- | --- |
| `id` | INT UNSIGNED AUTO_INCREMENT | Identifiant du compte |
| `nom` | VARCHAR(100) | Nom de l'etudiant |
| `prenom` | VARCHAR(100) | Prenom de l'etudiant |
| `email` | VARCHAR(190) UNIQUE | Email de connexion |
| `mot_de_passe` | VARCHAR(255) | Hash du mot de passe |
| `photo` | VARCHAR(255) NULL | Nom du fichier photo |
| `theme` | ENUM('light', 'dark') | Theme prefere |
| `date_creation` | DATETIME | Date de creation du compte |

### Table `publications`

| Champ | Type | Role |
| --- | --- | --- |
| `id` | INT UNSIGNED AUTO_INCREMENT | Identifiant de publication |
| `etudiant_id` | INT UNSIGNED | Auteur de la publication |
| `titre` | VARCHAR(150) | Titre |
| `contenu` | TEXT | Contenu |
| `date_creation` | DATETIME | Date de creation |
| `date_modification` | DATETIME NULL | Date de modification |

La cle etrangere `etudiant_id` pointe vers `etudiants(id)` avec suppression en cascade.

### Table `commentaires`

| Champ | Type | Role |
| --- | --- | --- |
| `id` | INT UNSIGNED AUTO_INCREMENT | Identifiant du commentaire |
| `publication_id` | INT UNSIGNED | Publication commentee |
| `etudiant_id` | INT UNSIGNED | Auteur du commentaire |
| `contenu` | TEXT | Contenu du commentaire |
| `date_creation` | DATETIME | Date de creation |

Les cles etrangeres pointent vers `publications(id)` et `etudiants(id)` avec suppression en cascade.

## Mecanismes de session utilises

- La session est demarree dans `functions.php`.
- Le cookie de session est configure avec `HttpOnly` et `SameSite=Lax`.
- L'identifiant de l'etudiant connecte est stocke dans `$_SESSION['etudiant_id']`.
- Le theme courant est stocke dans `$_SESSION['theme']` et synchronise avec la base.
- Les messages temporaires utilisent `$_SESSION['flash']`.
- Le token CSRF est stocke dans `$_SESSION['csrf_token']`.
- A la deconnexion, `$_SESSION` est vide, le cookie de session expire et `session_destroy()` est appele.

## Fichiers principaux

- `db.php` : connexion PDO.
- `functions.php` : session, CSRF, helpers, upload, rendu commun.
- `index.php` : connexion.
- `register.php` : inscription.
- `dashboard.php` : tableau de bord.
- `profile.php` : modification du profil et photo.
- `password.php` : changement du mot de passe.
- `delete_account.php` : suppression du compte.
- `publications.php` : liste, recherche et pagination.
- `publication.php` : detail et commentaires.
- `publication_create.php`, `publication_edit.php`, `publication_delete.php` : CRUD.
- `comment_add.php`, `comment_delete.php` : actions commentaires.
- `theme.php` : changement de theme.
- `logout.php` : deconnexion.
- `database.sql` : schema MySQL.
- `assets/css/style.css` : styles de l'interface.

## Scenarios de test conseilles

- Creer un compte valide.
- Tenter une inscription avec email deja utilise.
- Tenter une inscription avec champs vides.
- Tenter une connexion avec mauvais mot de passe.
- Acceder a `dashboard.php` sans connexion.
- Modifier le profil avec un email deja pris.
- Changer le mot de passe avec ancien mot de passe incorrect.
- Creer, modifier et supprimer une publication personnelle.
- Tenter de modifier une publication appartenant a un autre etudiant.
- Rechercher une publication.
- Tester la pagination avec plus de cinq publications.
- Ajouter et supprimer un commentaire.
- Tenter de supprimer le commentaire d'un autre etudiant sans etre proprietaire de la publication.
- Envoyer une photo valide puis un fichier non image.
- Changer le theme clair/sombre.
- Supprimer le compte et verifier la redirection vers la connexion.
