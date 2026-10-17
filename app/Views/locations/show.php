<?php
use App\Models\Location;
$status=$row['statut']; $id=(int) $row['id'];
$actionButton=static function (string $action,string $caption,bool $danger=false) use ($id): void { ?>
<form method="post" action="<?= e(url('location-action')) ?>" <?= $danger ? 'data-confirm="Confirmer cette action ?"' : '' ?>><?= csrf() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="<?= e($action) ?>"><button class="<?= $danger ? 'secondary danger' : '' ?>"><?= e($caption) ?></button></form>
<?php };
?>
<a class="back" href="<?= e(url('locations')) ?>">Retour aux locations</a>
<div class="page-heading"><h1><?= e($title) ?></h1><span class="status <?= e($status) ?>"><?= e(label($status)) ?></span></div>
<section class="panel"><h2>Détails de la location</h2><dl class="details">
    <div><dt>Client</dt><dd><?= e($row['client_prenom'].' '.$row['client_nom']) ?><small><?= e($row['client_email']) ?></small></dd></div>
    <div><dt>Équipement</dt><dd><?= e($row['equipement']) ?><small><?= e($row['categorie']) ?></small></dd></div>
    <div><dt>Période</dt><dd><?= e(dateFr($row['date_debut']).' au '.dateFr($row['date_fin'])) ?><small><?= Location::days($row) ?> jour(s), dates incluses</small></dd></div>
    <div><dt>Quantité</dt><dd><?= (int) $row['quantite'] ?></dd></div>
    <div><dt>Tarif appliqué / jour / unité</dt><dd><?= e(money($row['tarif_applique'])) ?></dd></div>
    <div><dt>Agent</dt><dd><?= e($row['agent_nom'] ?: 'Non affecté') ?></dd></div>
    <div><dt>Frais additionnels</dt><dd><?= e(money($row['frais_additionnels'])) ?></dd></div>
    <div><dt>Total <?= $status!=='terminee' ? 'prévisionnel' : '' ?></dt><dd class="total"><?= e(money(Location::totalCents($row)/100)) ?></dd></div>
    <div><dt>Paiement</dt><dd><?= $row['date_paiement'] ? e(money($row['montant_paye']).' — '.dateFr($row['date_paiement'])) : 'Non enregistré' ?></dd></div>
</dl>
<div class="actions">
<?php if (isRole('client','agent') && $status==='en_attente'): ?><a class="button secondary" href="<?= e(url('location-form',['id'=>$id])) ?>">Modifier la demande</a><?php endif ?>
<?php if (isRole('agent') && $status==='en_attente'): $actionButton('confirmer','Confirmer la location'); $actionButton('refuser','Refuser',true); endif ?>
<?php if (isRole('client','agent') && in_array($status,['en_attente','confirmee'],true)): $actionButton('annuler','Annuler la location',true); endif ?>
<?php if (isRole('agent') && $status==='confirmee'): $actionButton('depart','Enregistrer le départ'); endif ?>
<?php if (isRole('agent') && $status==='terminee' && !$row['date_paiement']): $actionButton('payer','Enregistrer le paiement intégral'); endif ?>
<?php if ((isRole('client') && $status==='en_attente') || (isRole('agent') && in_array($status,['en_attente','refusee','annulee'],true))): ?>
<form method="post" action="<?= e(url('location-delete')) ?>" data-confirm="Supprimer définitivement cette demande ?"><?= csrf() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="secondary danger">Supprimer la demande</button></form><?php endif ?>
</div></section>
<?php if (in_array($status,['confirmee','en_cours','retour_a_controler','terminee'],true)): ?>
<section class="panel"><h2>Documents</h2><p class="muted">Les documents conservent les coordonnées et le tarif enregistrés à la confirmation. La facture est provisoire jusqu’à la clôture du retour.</p><div class="actions">
<?php foreach (['contrat'=>'Contrat PDF','facture'=>'Facture PDF']+($row['date_paiement'] ? ['recu'=>'Reçu PDF'] : []) as $type=>$caption): ?><a class="button secondary" href="<?= e(url('document',['id'=>$id,'type'=>$type])) ?>"><?= e($caption) ?></a><?php endforeach ?>
</div></section><?php endif ?>
<?php if ($row['date_retour']): ?>
<section class="panel"><h2>Retour enregistré</h2><dl class="details"><div><dt>Date</dt><dd><?= e(dateFr($row['date_retour'])) ?></dd></div><div><dt>Quantités</dt><dd><?= (int) $row['retour_maintenance'] ?> en maintenance · <?= (int) $row['retour_endommage'] ?> endommagée(s)</dd></div><div><dt>Remarques</dt><dd class="preline"><?= e($row['remarques_retour'] ?: 'Aucune remarque') ?></dd></div></dl></section>
<?php endif ?>
<?php if ((isRole('agent') && $status==='en_cours') || (isRole('inventaire') && $status==='retour_a_controler')): $inspect=isRole('inventaire'); ?>
<section class="panel"><h2><?= $inspect ? 'Contrôler et valider le retour' : 'Enregistrer le retour' ?></h2><p class="muted">Retour de la totalité des <?= (int) $row['quantite'] ?> unité(s). Les autres unités retournent en état disponible après validation.</p>
<form method="post" action="<?= e(url('location-action')) ?>" class="form-grid" data-validate="retour" novalidate>
<?= csrf() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="<?= $inspect ? 'valider_retour' : 'retour' ?>">
<?php if (!$inspect): field('date_retour','Date de retour',date('Y-m-d'),'date'); field('frais_additionnels','Frais additionnels','0.00'); endif ?>
<?php field('retour_maintenance','Quantité en maintenance',$row['retour_maintenance']); field('retour_endommage','Quantité endommagée',$row['retour_endommage']); ?>
<label class="wide">Remarques<textarea name="remarques_retour" rows="3"><?= e($row['remarques_retour']) ?></textarea></label>
<p class="form-error wide" role="alert"></p><div class="wide"><button><?= $inspect ? 'Valider le retour et clôturer' : 'Transmettre au contrôle inventaire' ?></button></div>
</form></section><?php endif ?>
