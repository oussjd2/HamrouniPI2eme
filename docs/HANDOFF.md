# Reprise du projet

## Demande et état

Projet universitaire de gestion de location d’équipements, commencé à zéro. Garder le périmètre simple, les quatre entités du sujet, PHP 8 MVC PDO sans framework, interface française et GitHub privé. L’utilisateur a autorisé les étapes 1 à 17; les vérifications GUI et manuelles doivent avoir lieu après l’implémentation, à l’étape 18.

L’implémentation et les vérifications automatisées sont terminées. Se référer à VERIFICATION.md pour les preuves et limites. Ne pas annoncer que les contrôles visuels ou l’acceptation manuelle sont déjà faits.

## Accès

- Projet : `C:\Users\OUSSAMA\Desktop\projects\HamrouniPI2eme`.
- URL : `http://localhost/hamrouni/` avec Apache et MySQL XAMPP démarrés.
- PHP à utiliser : `C:\Users\OUSSAMA\Desktop\tools\xampp\php\php.exe` (8.0.30). Le PHP du PATH est en 8.2.30.
- Base locale : `hamrouni_location`. Accès dans `config/local.php`, ignoré par Git.
- Dépôt privé : `https://github.com/oussjd2/HamrouniPI2eme`.
- Comptes : voir README.md. Base laissée propre avec les données de démonstration initiales, sans locations.

## Prochaine action

Étape 18 : navigateur et scénario manuel, contrôle responsive et inspection visuelle des PDF. Aucune validation visuelle de l’application n’a encore été effectuée. Ensuite, corriger uniquement les constats et finaliser l’acceptation.

## Points métier à conserver

- Produits avec quantités; une location = un produit × une quantité.
- Dates inclusives; demandes en attente sans réservation; confirmation et départ revérifient le stock sous verrou.
- Les états sont répartis en quantités et peuvent coexister sur un produit.
- Retour complet par l’agent, validation inventaire, paiement intégral après clôture et reçu ensuite.
- Contrats/factures utilisent le tarif et les identités figés à la confirmation.
- Les retards et retours non contrôlés bloquent les unités. Un dommage réel est enregistré même s’il compromet une réservation future; le départ reste bloqué tant que le stock est insuffisant.
- Les tests métier utilisent une base isolée et les tests HTTP nettoient uniquement leurs propres fixtures.
