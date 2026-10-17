<a class="back" href="<?= e(url()) ?>">Retour au catalogue</a>
<div class="page-heading"><div><h1><?= e($equipment['nom']) ?></h1><p class="muted"><?= e($equipment['categorie']) ?></p></div><strong class="price"><?= e(money($equipment['tarif_journalier'])) ?><small> / jour</small></strong></div>
<section class="panel"><h2>Description</h2><p class="preline"><?= e($equipment['description']) ?></p>
<dl class="details"><div><dt>Disponible aujourd’hui</dt><dd><?= (int) $equipment['disponible'] ?> unité(s)</dd></div><div><dt>Durée</dt><dd>À la journée, dates incluses</dd></div></dl>
<p class="muted">La disponibilité sera vérifiée avec les dates et la quantité de votre demande, puis à la confirmation par l’agent.</p>
<?php if (isRole('client','agent')): ?><a class="button" href="<?= e(url('location-form',['equipement_id'=>$equipment['id']])) ?>">Demander une location</a>
<?php elseif (!user()): ?><a class="button" href="<?= e(url('connexion')) ?>">Se connecter pour louer</a><?php endif ?>
</section>
