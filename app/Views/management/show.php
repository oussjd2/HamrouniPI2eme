<a class="back" href="<?= e(url('gestion',['entite'=>$entity])) ?>">Retour à la liste</a><div class="page-heading"><h1><?= e($row['nom']) ?></h1><a class="button" href="<?= e(url('gestion-form',['entite'=>$entity,'id'=>$id])) ?>">Modifier</a></div>
<div class="panel"><dl class="details">
<?php
$fields=$entity==='utilisateurs' ? ['nom'=>'Nom','prenom'=>'Prénom','email'=>'E-mail','role'=>'Rôle'] : ($entity==='categories' ? ['nom'=>'Nom','description'=>'Description'] : ['nom'=>'Nom','categorie'=>'Catégorie','description'=>'Description','tarif_journalier'=>'Tarif journalier','quantite_totale'=>'Stock total','quantite_maintenance'=>'En maintenance','quantite_endommagee'=>'Endommagé','seuil_alerte'=>'Seuil d’alerte']);
foreach ($fields as $key=>$caption): ?>
<div><dt><?= e($caption) ?></dt><dd class="preline"><?= e($key==='role' ? label($row[$key]) : ($key==='tarif_journalier' ? money($row[$key]) : $row[$key])) ?></dd></div>
<?php endforeach ?></dl></div>
