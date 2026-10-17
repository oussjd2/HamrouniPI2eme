# Vérification et suivi

Vérification effectuée le 17 octobre 2026. Les étapes 1 à 18 sont terminées : implémentation, contrôles automatisés, parcours navigateur et inspection visuelle des PDF. L’acceptation personnelle de l’utilisateur reste distincte de ces vérifications.

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
| 14. PDF | Contrat, facture et reçu générés et téléchargés; droits, identités figées, apparence et pagination inspectés. |
| 15. Interface | Templates français, CSS responsive et JavaScript local implémentés et contrôlés dans Chrome desktop/mobile/tablette. |
| 16. Contrôles et livraison | Syntaxe PHP/JS, Composer, documentation d’installation, comptes et guide de présentation. |
| 17. Vérification fonctionnelle | 71 contrôles métier et 68 contrôles HTTP via Apache réussis après les corrections de l’étape 18. |

## Preuves automatisées

- `scripts/check.php` : tous les fichiers PHP de l’application, configuration, scripts et tests passent la vérification de syntaxe avec PHP XAMPP.
- `tests/integration.php` : 71 contrôles sur une base `hamrouni_test_*` créée vide puis nettoyée. Comprend CRUD, validations, protections d’historique, calculs, dates inclusives, locations successives, retards, retour, inspection, paiement et génération PDF.
- Deux processus PHP distincts confirment simultanément des demandes portant sur les mêmes unités : une confirmation acceptée, l’autre refusée; la quantité réservée reste dans la capacité.
- `tests/http.php` : 68 contrôles par requêtes cURL à `http://localhost/hamrouni/`. Comprend les contrôleurs, sessions, formulaires, rôles, propriété des documents, CSRF, échappement HTML, conservation des formulaires en erreur et parcours complet. Les données temporaires sont retirées; ce script reste distinct des parcours navigateur ci-dessous.
- Composer : fichier valide et aucune alerte de vulnérabilité connue signalée par l’audit à la date de vérification.
- Base de démonstration après nettoyage : 3 utilisateurs, 3 catégories, 7 équipements, 0 location.

## Corrections issues de la vérification

- Code HTTP CSRF : utilisation du code standard 403, l’environnement Apache remplaçant le code non standard 419 par 500.
- Transactions : isolation READ COMMITTED et verrou de ligne équipement. Cela évite qu’une confirmation utilise une ancienne vue des réservations après l’attente d’un verrou.
- Relecture des droits après verrouillage, détection d’un changement concurrent d’équipement, limite des montants et saisies malformées protégées.

## Étape 18 — terminée

- [x] Parcourir les pages publiques et les pages autorisées des trois rôles dans Chrome.
- [x] Exercer les quatre CRUD par les formulaires, liens, boutons et dialogues du navigateur, avec suppressions autorisées et protégées.
- [x] Effectuer deux parcours client → agent → inventaire → paiement → téléchargement, dont un après correction.
- [x] Vérifier les erreurs JavaScript et PHP, messages de succès/refus, annulation/refus de location et annulation du dialogue de suppression.
- [x] Vérifier desktop (1440 et 1774 px), tablette (768 px), mobile (390 et 320 px), navigation, formulaires, tableaux et filtres.
- [x] Télécharger les documents, rendre et inspecter toutes leurs pages : accents, montants, signatures, retours, débordements, pieds de page et lisibilité.
- [x] Comparer le rendu à la direction visuelle de DESIGN.md, avec les adaptations documentées.

### Méthode et preuves

Browser plugin absent : Playwright a piloté Chrome installé, sans installer de dépendance navigateur dans le projet. Les formulaires et contrôles HTML réels ont été utilisés, avec sessions séparées pour les trois rôles. Il s’agit de scénarios opérés par l’agent dans un navigateur; aucun test par une personne sur téléphone physique n’est revendiqué.

Premier passage : 43 points de contrôle réussis. Passage ciblé après corrections : 24 points réussis, incluant la suppression des catégories/équipements sans historique, l’inscription publique, le refus et l’annulation. Console sans erreur ou avertissement sur les parcours. Onze familles de pages capturées à 390 px, sans débordement horizontal du document; les tableaux larges défilent dans leur conteneur et leurs actions restent accessibles. Filtres et location finale vérifiés après rechargement.

Le scénario de contrôle calcule 2 unités × 1 jour × 13,00 TND = 26,00 TND, puis 4,50 TND de frais = 30,50 TND. Le premier retour place une unité en état endommagé; le stock disponible passe de 5 à 4. Le paiement, les documents et le résultat rechargé restent cohérents.

Cinq PDF ordinaires inspectés après le dernier correctif (contrat confirmé/final, facture provisoire/finale, reçu), une page chacun. Trois variantes de contenu long inspectées, deux pages chacune : noms aux longueurs maximales, e-mail long, équipement/catégorie sans espaces et remarques sur plusieurs lignes. Total : 11 pages inspectées. Poppler a produit les rendus PNG; pypdf a confirmé la présence du pied de page et de la pagination sur chacune des pages.

Captures, résultats et scripts temporaires sont conservés hors dépôt :
`C:\Users\OUSSAMA\.codex\visualizations\2026\10\17\01a14acc-8e3d-7ed1-9bde-b710a0217520\step18`.
Fichiers principaux : `browser-results.json`, `recheck-results.json`, `final-desktop.png`, `final-mobile.png`, `return-validation-fixed.png`, `contrat-final.pdf`, `facture-finale.pdf`, `recu.pdf` et `stress-*.pdf`.

### Constats corrigés et revérifiés

1. Retour/inspection : les frais, quantités et remarques étaient effacés après une erreur serveur. Les valeurs saisies sont désormais conservées pour la location concernée, sans modifier les données enregistrées avant validation. Vérifié dans le navigateur et par quatre nouveaux contrôles HTTP.
2. Filtre stock en alerte : case et texte empilés avec un espace inutile. Alignement corrigé desktop/mobile; activation et résultat vérifiés.
3. PDF : e-mail long sortant du cadre client. Retour à la ligne corrigé et inspecté sur les trois types de document.
4. PDF multipages : pied de page et pagination homogènes ajoutés sur chaque page; titres conservés avec le contenu suivant. Les 11 pages ont été rendues et inspectées après correction.

Les données temporaires du navigateur ont été retirées après vérification de leurs identités exactes. Base laissée avec les 3 comptes, 3 catégories et 7 équipements d’origine, sans location.

## Étape 19 — après les contrôles visuels

- [x] Corriger les constats et répéter les contrôles concernés dans le cadre de l’étape 18.
- [x] Parcours de démonstration effectué par l’agent.
- [ ] Acceptation personnelle et préparation de la présentation avec l’utilisateur / son ami.
- [x] Corrections et documentation enregistrées dans le dépôt privé.

La vérification est limitée à Chrome sur Windows et à des tailles d’écran simulées. Safari, Firefox et téléphone physique n’ont pas été testés. Aucun défaut bloquant connu ne reste à l’issue de l’étape 18.

## Extension demandée : photos produit

Ajout demandé après l’étape 18 : une photo facultative par équipement, sept illustrations initiales, ajout/remplacement/suppression depuis le formulaire inventaire. Les photos sont affichées dans le catalogue, les fiches et les listes de gestion; un aperçu est disponible avant enregistrement. Les produits sans photo affichent une illustration neutre. Aucune nouvelle entité ni aucun framework.

La base locale a été mise à jour sans réinitialisation avec `scripts/update-product-images.php`. La seconde exécution a confirmé qu’aucune donnée n’était modifiée. Le schéma et les données initiales couvrent également une installation neuve. Les photos initiales sont conservées localement, avec sources, auteurs et licences dans `public/assets/products/credits.json` et la page publique « Crédits photos ».

Validation après ajout :

- 45 fichiers PHP sans erreur de syntaxe, JavaScript valide, Composer valide et aucune alerte de dépendance connue lors de la mise à jour du lock.
- 71 contrôles métier et 68 contrôles HTTP existants réussis.
- 22 contrôles dédiés dans `tests/images.php` : transferts réels JPG/PNG/WebP, conservation sans remplacement, nettoyage des anciens fichiers, suppression, faux JPG/SVG/plus de 2 Mo refusés, fichiers manquants, noms aléatoires, stockage hors webroot, absence de chemin contrôlé par le POST, illustration neutre et crédits. Les fichiers partagés des illustrations initiales sont préservés.
- 17 points de contrôle navigateur réussis dans Chrome : sept photos du catalogue et des fiches chargées, miniatures, aperçu, création avec fichier, modification conservant la photo, remplacement, suppression, contrôles JS et mise en page de cinq surfaces à 320/390/768 px. Aucune erreur console ni image cassée. Desktop vérifié à 1440 px.
- Captures de catalogue, fiche, formulaire et gestion inspectées visuellement. Proportions conservées sans recadrage et sans débordement du document; tableaux défilants sur téléphone.

Preuves hors dépôt :
`C:\Users\OUSSAMA\.codex\visualizations\2026\10\17\01a14acc-8e3d-7ed1-9bde-b710a0217520\product-images`.
Résultat principal : `results.json`; captures : `catalogue-desktop.png`, `camera-detail-desktop.png`, `camera-detail-mobile.png`, `image-form-mobile.png` et `management-mobile.png`.

Les enregistrements et fichiers temporaires de cette validation ont été retirés. Base conservée à 3 utilisateurs, 3 catégories, 7 équipements illustrés, 0 location.
