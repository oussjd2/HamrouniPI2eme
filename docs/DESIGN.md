# Interface

Direction : fond blanc, texte bleu marine, actions turquoise, bordures gris clair, tableaux lisibles, formulaires simples. Templates PHP partagés : barre supérieure, navigation par rôle, messages, pages de liste, détail et formulaire. CSS et JavaScript locaux, sans framework ni service externe.

Le concept de référence a été généré avec l’outil Image Gen intégré, à partir du brief : « écran de gestion français Locamat, navigation Catalogue/Locations/Équipements/Catégories/Utilisateurs, recherche et tableau, fond blanc, bleu marine, turquoise, sans graphiques ni indicateurs ajoutés ». Il est conservé dans `docs/design-reference.png` comme guide de style, sans validation visuelle finale à ce stade.

Adaptations intentionnelles au périmètre : pas de photos produit, références commerciales, rôle Admin, icônes décoratives ou devise euro inventés par le concept. Utilisation des trois rôles du sujet et de TND pour les exemples. Affichage séparé des quantités par état, puisqu’un produit peut avoir plusieurs états simultanément. Le catalogue et les formulaires suivent les mêmes styles que les tableaux.

Les media queries adaptent la navigation, les formulaires et le catalogue aux petits écrans; les grands tableaux disposent d’un défilement horizontal. La validation navigateur et responsive reste à faire à l’étape 18.
