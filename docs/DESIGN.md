# Interface

Direction : fond blanc, texte bleu marine, actions turquoise, bordures gris clair, tableaux lisibles, formulaires simples. Templates PHP partagés : barre supérieure, navigation par rôle, messages, pages de liste, détail et formulaire. CSS et JavaScript locaux, sans framework ni service externe.

Le concept de référence a été généré avec l’outil Image Gen intégré, à partir du brief : « écran de gestion français Locamat, navigation Catalogue/Locations/Équipements/Catégories/Utilisateurs, recherche et tableau, fond blanc, bleu marine, turquoise, sans graphiques ni indicateurs ajoutés ». Il est conservé dans `docs/design-reference.png` comme guide de style. Le rendu et le concept ont été inspectés visuellement pendant l’étape 18, y compris à la taille native du concept (1774 × 887).

Adaptations intentionnelles au périmètre : pas de photos produit, références commerciales, rôle Admin, icônes décoratives ou devise euro inventés par le concept. Utilisation des trois rôles du sujet et de TND pour les exemples. Affichage séparé des quantités par état, puisqu’un produit peut avoir plusieurs états simultanément. Le catalogue et les formulaires suivent les mêmes styles que les tableaux.

Les media queries adaptent la navigation, les formulaires et le catalogue aux petits écrans; les grands tableaux disposent d’un défilement horizontal interne. Vérifié dans Chrome à 320, 390, 768, 1440 et 1774 px.

## Comparaison visuelle de l’étape 18

| Point | Rendu et décision |
|---|---|
| Couleurs | Fond blanc, texte bleu marine, actions turquoise et bordures grises conservés. |
| Structure | Barre supérieure, navigation à gauche, titre, filtres et tableau conservés. Navigation repliée en lignes sur mobile. |
| Typographie | Titres, contenu, libellés, boutons et messages hiérarchisés et lisibles. Taille du titre plus compacte que le concept pour l’application universitaire. |
| Contenu | Libellés français du sujet; trois rôles réels, TND, quantités par état. Pas de texte marketing ajouté. |
| Contrôles | Filtres supplémentaires exigés par le périmètre; ligne de la case alerte corrigée et revérifiée. |
| Espacement et responsive | Formulaires en une colonne sur téléphone, catalogue en une colonne, tableaux défilants sans débordement du document. |
| Images et icônes | Absence intentionnelle des photos et icônes décoratives du concept, déjà documentée; aucun média manquant. |

La comparaison porte sur la direction visuelle et les adaptations convenues, pas sur une reproduction pixel par pixel du concept. Aucun écart bloquant observé après corrections.
