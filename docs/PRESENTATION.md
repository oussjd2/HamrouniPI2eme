# Guide de présentation

## Parcours de démonstration

1. Ouvrir le catalogue sans connexion : rechercher un équipement par nom, catégorie, tarif et dates.
2. Se connecter comme client; demander deux unités pour aujourd’hui. Consulter puis modifier la demande en attente.
3. Se connecter comme agent; ouvrir la demande, vérifier le tarif et confirmer. Télécharger le contrat et la facture provisoire.
4. Enregistrer le départ. Enregistrer ensuite le retour avec une unité endommagée et des frais additionnels.
5. Se connecter comme responsable inventaire; filtrer les locations « Retour à contrôler », inspecter et valider le retour.
6. Vérifier que les quantités en bon état sont disponibles et que l’unité endommagée reste indisponible.
7. Comme agent, enregistrer le paiement intégral. Comme client, télécharger la facture définitive et le reçu.
8. Montrer les CRUD, une alerte de stock et le refus de supprimer un équipement lié à une location.

## Questions à savoir expliquer

**Pourquoi MVC ?** Les modèles gèrent la base et les règles, les contrôleurs traitent les requêtes, les vues affichent les pages. `public/index.php` choisit le contrôleur à partir de la route `r`.

**Pourquoi PDO ?** Le sujet l’impose. Une connexion commune fournit des requêtes préparées avec des paramètres séparés des instructions SQL. Aucune requête MySQLi.

**Où sont les jointures ?** Les équipements sont joints à leur catégorie. Les locations sont jointes au client, à l’agent, à l’équipement et à sa catégorie. La liste des catégories utilise une jointure pour compter ses équipements.

**Comment les disponibilités sont-elles calculées ?** Stock total moins maintenance et endommagé, puis moins le maximum des quantités réservées simultanément sur les dates demandées. Deux locations successives ne sont pas additionnées comme si elles avaient lieu le même jour. Les dates de fin sont incluses.

**Comment éviter deux confirmations pour les mêmes unités ?** Une transaction verrouille l’équipement, revérifie la disponibilité, puis confirme. Le second traitement attend et voit le stock actualisé.

**Pourquoi plusieurs quantités d’état ?** Un produit représente plusieurs unités. Certaines peuvent être louées, d’autres disponibles, en maintenance ou endommagées au même moment.

**Comment le prix est-il calculé ?** Quantité × jours inclusifs × tarif journalier appliqué, plus les frais. Le calcul passe par des centimes entiers. Le tarif et les identités sont figés à la confirmation.

**Pourquoi séparer retour et inspection ?** L’agent enregistre la restitution; le responsable inventaire contrôle l’état. Le matériel reste réservé jusqu’à cette validation. Si un dommage compromet une réservation future, l’agent doit résoudre la disponibilité avant le départ; le contrôle de départ refuse un stock insuffisant.

**Quelle différence entre facture et reçu ?** La facture indique les montants dus. Le reçu prouve l’enregistrement du paiement intégral après clôture. Le système n’encaisse pas d’argent en ligne.

**Comment sont protégés les comptes ?** Hachage `password_hash`, contrôle `password_verify`, sessions, rôle relu depuis la base, contrôle de propriété des locations. L’inscription impose le rôle client.

**Pourquoi CSRF et échappement HTML ?** Le jeton protège les actions envoyées par formulaire. L’échappement empêche qu’un texte saisi soit interprété comme du HTML ou du JavaScript.

**Pourquoi certaines suppressions sont-elles bloquées ?** Les clés étrangères et les règles de l’application protègent les documents et l’historique. Une demande non engagée peut être supprimée; une location confirmée doit être conservée.

**Dompdf est-il un framework ?** Non. C’est une bibliothèque utilisée pour convertir un template HTML en PDF. Le routage, MVC, les formulaires et les règles sont écrits en PHP sans framework.

## Fichiers à parcourir avant la soutenance

1. `database/schema.sql` et `docs/SCOPE.md`.
2. `public/index.php`, `app/Core/Database.php`, `app/Core/Auth.php`.
3. `app/Models/Equipement.php` et `app/Models/Location.php`.
4. Un contrôleur, sa vue, puis `app/Controllers/DocumentController.php`.
5. Les scripts de test et le résultat de vérification.
