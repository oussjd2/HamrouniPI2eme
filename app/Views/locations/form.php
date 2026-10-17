<a class="back" href="<?= e(url('locations')) ?>">Retour aux locations</a><div class="page-heading"><h1><?= e($title) ?></h1></div>
<?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif ?>
<form method="post" class="panel form-grid" data-validate="location" novalidate>
<?= csrf() ?>
<?php if (isRole('agent')): ?><label>Client<select name="client_id"><option value="">Choisir un client</option><?php foreach ($clients as $client): ?><option value="<?= (int) $client['id'] ?>" <?= (string) ($row['client_id'] ?? '')===(string) $client['id'] ? 'selected' : '' ?>><?= e($client['prenom'].' '.$client['nom'].' — '.$client['email']) ?></option><?php endforeach ?></select></label><?php endif ?>
<label>Équipement<select name="equipement_id" id="rental-equipment"><option value="">Choisir un équipement</option><?php foreach ($equipements as $equipment): ?><option value="<?= (int) $equipment['id'] ?>" data-price="<?= e($equipment['tarif_journalier']) ?>" <?= (string) ($row['equipement_id'] ?? '')===(string) $equipment['id'] ? 'selected' : '' ?>><?= e($equipment['nom'].' — '.money($equipment['tarif_journalier']).'/jour') ?></option><?php endforeach ?></select></label>
<?php field('quantite','Quantité',$row['quantite'] ?? 1); field('date_debut','Date de début',$row['date_debut'] ?? '','date'); field('date_fin','Date de fin',$row['date_fin'] ?? '','date'); ?>
<div class="wide estimate"><strong>Estimation : <output id="rental-estimate">—</output></strong><p class="muted">Dates incluses. Tarif vérifié à la confirmation. Les éventuels frais de retour seront ajoutés séparément.</p></div>
<p class="form-error wide" role="alert"></p><div class="wide actions"><button>Enregistrer la demande</button><a href="<?= e(url('locations')) ?>">Annuler</a></div>
</form>
