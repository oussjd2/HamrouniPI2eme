<div class="page-heading"><div><h1>Catalogue des équipements</h1><p class="muted">Choisissez votre matériel et vérifiez les disponibilités pour vos dates.</p></div></div>
<?php require __DIR__ . '/filters.php'; ?>
<p class="muted"><?= count($equipements) ?> équipement(s) · Disponibilité sur la période choisie, aujourd’hui par défaut.</p>
<div class="catalogue-grid">
<?php foreach ($equipements as $equipment): ?>
    <article class="product panel">
        <span class="category"><?= e($equipment['categorie']) ?></span>
        <h2><a href="<?= e(url('equipement',['id'=>$equipment['id']])) ?>"><?= e($equipment['nom']) ?></a></h2>
        <p><?= e(mb_strimwidth($equipment['description'],0,170,'…')) ?></p>
        <div class="product-bottom"><strong><?= e(money($equipment['tarif_journalier'])) ?><small> / jour</small></strong><span class="status <?= $equipment['disponible'] ? 'terminee' : 'annulee' ?>"><?= (int) $equipment['disponible'] ?> disponible(s)</span></div>
        <a class="button secondary" href="<?= e(url('equipement',['id'=>$equipment['id']])) ?>">Voir l’équipement</a>
    </article>
<?php endforeach ?>
</div>
<?php if (!$equipements): ?><div class="empty panel">Aucun équipement ne correspond à votre recherche.</div><?php endif ?>
