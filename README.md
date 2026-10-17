# Locamat — Gestion de location d’équipements

Projet universitaire réalisé en **PHP 8, MVC, PDO MySQL**, sans framework. Interface française, catalogue client et back-office pour l’agent de location et le responsable inventaire.

## Démarrage sur cette machine

1. Dans XAMPP, démarrer **Apache** et **MySQL**.
2. Ouvrir **http://localhost/hamrouni/**.

Le dossier `htdocs/hamrouni` est une jonction Windows vers le seul dossier `public` de ce projet. Le code, la configuration, les scripts et les dépendances ne sont pas exposés par Apache.

| Rôle | E-mail | Mot de passe de démonstration |
|---|---|---|
| Responsable inventaire | inventaire@locamat.test | Demo2026! |
| Agent de location | agent@locamat.test | Demo2026! |
| Client | client@locamat.test | Demo2026! |

Ces comptes sont des données de démonstration locale. Les mots de passe sont hachés dans la base. L’inscription publique crée uniquement des clients.

## Installation sur une autre machine

Prévoir XAMPP avec PHP 8.0 ou supérieur, PDO MySQL, mbstring, DOM, XML, iconv et Composer. PHP doit être en 64 bits pour les calculs entiers. Pour lancer les tests HTTP, l’extension cURL est nécessaire.

1. Cloner le dépôt privé dans un dossier de travail, hors du répertoire public Apache.
2. Copier `config/example.php` vers `config/local.php` et renseigner les accès MySQL/MariaDB. Ce fichier local est ignoré par Git.
3. Exécuter Composer avec **le PHP utilisé par XAMPP** :

```powershell
& 'C:\chemin\xampp\php\php.exe' 'C:\ProgramData\ComposerSetup\bin\composer.phar' install --no-interaction
& 'C:\chemin\xampp\php\php.exe' scripts\install.php
```

Le script crée la base et charge sept équipements, trois catégories et trois comptes. Il refuse une base qui contient déjà des tables; aucune suppression automatique des données.

4. Exposer uniquement `public` avec une jonction Windows (ou un VirtualHost Apache dont le DocumentRoot pointe vers `public`) :

```powershell
New-Item -ItemType Junction -Path 'C:\chemin\xampp\htdocs\hamrouni' -Target 'C:\chemin\projet\public'
```

5. Démarrer Apache et MySQL, puis ouvrir `http://localhost/hamrouni/`.

Sur cette machine, PHP est situé dans `C:\Users\OUSSAMA\Desktop\tools\xampp\php\php.exe`. Le PHP de la commande `php` dans le terminal est différent : utiliser le chemin XAMPP pour rester cohérent avec Apache.

## Structure

```text
app/
  Core/           PDO, authentification, validation et rendu
  Models/         Les quatre entités et leurs règles métier
  Controllers/    Traitement des requêtes et autorisations
  Views/          Templates PHP en français
config/           Configuration exemple et configuration locale ignorée
database/         Schéma SQL, quatre tables InnoDB
docs/             Périmètre, présentation, suivi et vérification
public/           Unique point d’entrée web et fichiers CSS/JavaScript
scripts/          Installation, données de démonstration, vérification PHP
storage/          Journaux locaux ignorés par Git
tests/            Vérifications métier et HTTP
vendor/           Bibliothèques PDF installées avec Composer, ignorées
```

## Fonctionnalités

- CRUD Utilisateur, Catégorie Équipement, Équipement et Location, avec règles de suppression pour préserver l’historique.
- Authentification par session et autorisations côté serveur pour les trois rôles.
- Catalogue, recherche multicritères, stock, seuils d’alerte et quantités disponibles/en location/en maintenance/endommagées.
- Demandes, confirmation, départ, retour, contrôle inventaire, frais et paiement intégral enregistré.
- Contrats, factures provisoires/définitives et reçus PDF générés avec Dompdf, bibliothèque sans framework.
- Requêtes PDO préparées, échappement HTML, CSRF, validation PHP et aide JavaScript. Aucune validation ne dépend uniquement du HTML.

Les règles détaillées sont dans [docs/SCOPE.md](docs/SCOPE.md). Les paiements sont enregistrés manuellement : aucun service bancaire n’est intégré. Les prix de démonstration sont en TND, sans règle fiscale ajoutée au sujet.

## Vérification automatisée

Depuis la racine du projet, avec Apache et MySQL démarrés :

```powershell
$phpExe = 'C:\Users\OUSSAMA\Desktop\tools\xampp\php\php.exe'
& $phpExe scripts\check.php
& $phpExe tests\integration.php
& $phpExe tests\http.php 'http://localhost/hamrouni/'
```

- `check.php` vérifie la syntaxe PHP de l’application et des scripts.
- `integration.php` installe une base `hamrouni_test_*` isolée, exerce les règles métier et la génération PDF, puis supprime uniquement cette base de test.
- `http.php` vérifie les contrôleurs à travers Apache, sans navigateur. Il crée ses propres enregistrements temporaires et les supprime en fin de test. Les comptes de démonstration doivent conserver leur mot de passe initial pour ce scénario.

Les journaux applicatifs sont dans `storage/logs/application.log`. Les erreurs techniques ne sont pas affichées aux visiteurs.

## État de livraison

Étapes 1 à 18 terminées : implémentation, contrôles automatisés, parcours GUI des trois rôles, responsive et inspection visuelle des PDF. Résultats et limites dans [docs/VERIFICATION.md](docs/VERIFICATION.md). La base de démonstration reste propre. L’acceptation personnelle et la préparation de la présentation peuvent maintenant être faites avec l’utilisateur.
