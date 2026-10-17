# Périmètre et règles

Application universitaire PHP 8, MVC, PDO MySQL, sans framework. Interface française. Les quatre entités imposées sont Utilisateur, Catégorie Équipement, Équipement et Location. Les dépendances Composer servent uniquement à la génération PDF.

## Rôles

- Client : inscription, catalogue, demandes, modification/suppression de ses demandes en attente, annulation avant départ, consultation de ses documents.
- Agent : gestion des clients, création et traitement des locations, départs, retours, frais, paiement et documents.
- Responsable inventaire : CRUD utilisateurs et rôles, catégories et équipements, recherche et alertes, contrôle et validation des retours. Consultation des locations et documents.

## Règles retenues

- Une location porte sur un produit et une quantité. Aucun panier, paiement en ligne ou entité supplémentaire.
- Dates inclusives : même date = un jour. Prix = quantité × jours × tarif appliqué + frais. Devise de démonstration : TND, prix arrondis au centime, sans fiscalité ajoutée non demandée.
- Les demandes ne réservent pas le stock. La confirmation vérifie la capacité maximale simultanément réservée, pas la somme des réservations successives.
- Les quantités confirmées/en cours/à contrôler réservent le stock. Un départ en retard et les retours non contrôlés bloquent le stock jusqu'à validation; aucune remise en location automatique.
- Stock disponible = stock total moins maintenance, endommagé et quantités réservées. Les états sont des quantités par produit, car un produit peut avoir plusieurs états simultanés.
- Les changements de stock ne peuvent rendre impossibles des réservations existantes. Les écritures concurrentes d'une même référence verrouillent la ligne équipement en transaction.
- Le départ est enregistré par l'agent pendant la période prévue. Le retour est total, enregistré entre départ prévu et aujourd'hui. L'agent renseigne les quantités à contrôler; le responsable peut les corriger à l'inspection et valide. Pas de retours partiels.
- En attente → confirmée/refusée/annulée; confirmée → en cours/annulée; en cours → retour à contrôler; retour à contrôler → terminée.
- Tarif et identités sont figés lors de la confirmation pour les documents. Les frais sont renseignés au retour. Facture provisoire avant clôture, définitive après clôture. Paiement intégral enregistré après clôture, puis reçu. Aucun encaissement réel.
- Suppression des demandes en attente/refusées/annulées uniquement. Les autres locations et les entités liées à l'historique sont protégées.
- Inscription publique : rôle client uniquement. Un responsable ne peut supprimer/modifier son propre rôle, ni retirer le dernier responsable.
- Validation PHP obligatoire et aide JS, requêtes préparées, mots de passe hachés, contrôle des rôles côté serveur, jetons CSRF et échappement HTML.

## Ordre de validation

Étapes 1–16 : environnement, implémentation, syntaxe, contrôles ciblés. Étape 17 : installation propre sur une base de test, contrôles automatisés fonctionnels et HTTP. Étape 18 : navigateur, responsive et vérification visuelle des PDF, terminés après autorisation de l’utilisateur. Étape 19 : corrections revérifiées; acceptation personnelle de l’utilisateur et préparation de la présentation à suivre.
