# Vérification et suivi

Vérification effectuée le 17 octobre 2026. Ce document distingue l’implémentation, les preuves automatisées et l’acceptation visuelle encore à réaliser.

## Étapes 1 à 17

| Étape | Résultat |
|---|---|
| 1. Environnement | XAMPP Apache 2.4.58, PHP 8.0.30 64 bits, PDO MySQL, MariaDB 10.4.32. Apache valide et accessible. Git, GitHub et Composer disponibles. |
| 2. Git privé | Dépôt `oussjd2/HamrouniPI2eme` privé, branche main, commits réguliers. Configuration locale, dépendances et journaux exclus. |
| 3. Périmètre | Quatre entités et règles documentées dans SCOPE.md. |
| 4. Base | Quatre tables InnoDB, clés étrangères, contraintes et index. Installation vérifiée sur une nouvelle base isolée. |
| 5. MVC | Point d’entrée unique, modèles PDO, contrôleurs, templates et composants communs. |
| 6. Authentification | Inscription client, connexion/déconnexion, mots de passe hachés, sessions et permissions vérifiés. |
| 7. Utilisateur | CRUD et protections contre les suppressions liées et changements de rôle non autorisés. |
| 8. Catégorie | CRUD, noms uniques et suppression protégée si équipements liés. |
| 9. Équipement | CRUD, stock, maintenance, dommages, seuils et recherche multicritères. |
| 10. Catalogue | Pages publiques, filtres, détails et formulaire client. Réponses HTTP vérifiées. |
| 11. Location | CRUD selon le statut, confirmation, refus, annulation et départ. Vérification des chevauchements et de la concurrence. |
| 12. Retours | Enregistrement agent, inspection inventaire, clôture et états de stock vérifiés en base. |
| 13. Facturation | Calcul en centimes, frais et paiement intégral après clôture, double paiement bloqué. |
| 14. PDF | Contrat, facture et reçu générés et téléchargés; droits et identités figées vérifiés. Apparence pas encore inspectée. |
| 15. Interface | Templates français, CSS responsive et JavaScript local implémentés. Rendu navigateur pas encore vérifié. |
| 16. Contrôles et livraison | Syntaxe PHP/JS, Composer, documentation d’installation, comptes et guide de présentation. |
| 17. Vérification fonctionnelle | 71 contrôles métier et 64 contrôles HTTP via Apache réussis. |

## Preuves automatisées

- `scripts/check.php` : tous les fichiers PHP de l’application, configuration, scripts et tests passent la vérification de syntaxe avec PHP XAMPP.
- `tests/integration.php` : 71 contrôles sur une base `hamrouni_test_*` créée vide puis nettoyée. Comprend CRUD, validations, protections d’historique, calculs, dates inclusives, locations successives, retards, retour, inspection, paiement et génération PDF.
- Deux processus PHP distincts confirment simultanément des demandes portant sur les mêmes unités : une confirmation acceptée, l’autre refusée; la quantité réservée reste dans la capacité.
- `tests/http.php` : 64 contrôles par requêtes cURL à `http://localhost/hamrouni/`. Comprend les contrôleurs, sessions, formulaires, rôles, propriété des documents, CSRF, échappement HTML et parcours complet. Les données temporaires sont retirées; aucun clic navigateur n’est simulé.
- Composer : fichier valide et aucune alerte de vulnérabilité connue signalée par l’audit à la date de vérification.
- Base de démonstration après nettoyage : 3 utilisateurs, 3 catégories, 7 équipements, 0 location.

## Corrections issues de la vérification

- Code HTTP CSRF : utilisation du code standard 403, l’environnement Apache remplaçant le code non standard 419 par 500.
- Transactions : isolation READ COMMITTED et verrou de ligne équipement. Cela évite qu’une confirmation utilise une ancienne vue des réservations après l’attente d’un verrou.
- Relecture des droits après verrouillage, détection d’un changement concurrent d’équipement, limite des montants et saisies malformées protégées.

## Étape 18 — à faire ensuite

- [ ] Parcourir toutes les pages dans un navigateur pour chacun des trois rôles.
- [ ] Effectuer manuellement les quatre CRUD et les cas de suppression protégée.
- [ ] Effectuer le scénario complet client → agent → inventaire → paiement → téléchargement.
- [ ] Vérifier les erreurs de saisie et les messages de succès/refus visibles.
- [ ] Vérifier les mises en page desktop et mobile, navigation, formulaires, tableaux et filtres.
- [ ] Ouvrir les trois types de PDF, rendre et inspecter toutes les pages : accents, montants, signatures, retours, débordements et lisibilité.
- [ ] Comparer le rendu à la direction visuelle de DESIGN.md, avec les adaptations documentées.

## Étape 19 — après les contrôles visuels

- [ ] Corriger les constats et répéter les contrôles concernés.
- [ ] Terminer le parcours de démonstration et l’acceptation avec l’utilisateur.
- [ ] Commit et push final des éventuelles corrections.

La réussite des tests automatisés ne constitue pas une validation graphique ou manuelle. Les étapes 18 et 19 restent volontairement ouvertes.
