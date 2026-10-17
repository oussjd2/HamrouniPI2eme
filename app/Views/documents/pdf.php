<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>
@page { margin: 45px 45px 55px; } body { font-family: 'DejaVu Sans',sans-serif; color: #172744; font-size: 10px; line-height: 1.6; } h1 { font-size: 23px; margin: 30px 0 4px; } h2 { font-size: 13px; margin-top: 24px; } .brand { font-size: 24px; font-weight: bold; color: #087f8c; } .muted { color: #5b6878; } table { border-collapse: collapse; width:100%; margin: 18px 0; table-layout: fixed; } td,th { border-bottom: 1px solid #dce3e9; padding: 10px 6px; text-align:left; overflow-wrap: break-word; } th { background: #edf5f6; } .number { text-align:right; } .total { font-size:16px; text-align:right; } .box { background:#f4f7fa; padding:14px; } .footer { position:fixed; bottom:-30px; font-size:8px; color:#5b6878; } .preline { white-space:pre-wrap; overflow-wrap:break-word; } tr { page-break-inside:avoid; }
</style><style>body,.box{word-wrap:break-word}.box{max-width:100%}h1,h2{page-break-after:avoid}</style></head><body>
<div class="brand">Locamat</div><div class="muted">Gestion de location d’équipements · Démonstration universitaire</div>
<h1><?= e($title) ?></h1><p>Référence <?= e(strtoupper($type)) ?>-<?= str_pad((string) $row['id'],6,'0',STR_PAD_LEFT) ?> · Location n°<?= (int) $row['id'] ?></p>
<?php if ($type==='facture' && $row['statut']!=='terminee'): ?><p><strong>FACTURE PROVISOIRE — frais de retour non définitifs.</strong></p><?php endif ?>
<div class="box"><strong>Client</strong><br><?= e($row['client_prenom'].' '.$row['client_nom']) ?><br><?= e($row['client_email']) ?></div>
<h2>Location du <?= e(dateFr($row['date_debut'])) ?> au <?= e(dateFr($row['date_fin'])) ?></h2>
<p><?= App\Models\Location::days($row) ?> jour(s), dates incluses · Statut : <?= e(label($row['statut'])) ?></p>
<table><thead><tr><th>Équipement</th><th>Quantité</th><th>Tarif / jour</th><th class="number">Montant</th></tr></thead><tbody><tr><td><?= e($row['equipement']) ?><br><span class="muted"><?= e($row['categorie']) ?></span></td><td><?= (int) $row['quantite'] ?></td><td><?= e(money($row['tarif_applique'])) ?></td><td class="number"><?= e(money((App\Models\Location::totalCents($row)-App\Core\Validation::cents($row['frais_additionnels']))/100)) ?></td></tr>
<tr><td colspan="3">Frais additionnels</td><td class="number"><?= e(money($row['frais_additionnels'])) ?></td></tr></tbody></table>
<p class="total"><strong>Total : <?= e(money(App\Models\Location::totalCents($row)/100)) ?></strong></p>
<?php if ($type==='contrat'): ?>
<h2>Conditions de location</h2><p>Le matériel est confié au client pour la quantité et la période indiquées. Le client doit restituer la totalité du matériel. L’état est contrôlé au retour par le responsable inventaire. Les éventuels frais additionnels sont enregistrés au retour et figurent sur la facture définitive.</p>
<p>Signature du client : ____________________</p><p>Signature de l’agent : ____________________</p>
<?php elseif ($type==='recu'): ?><div class="box"><strong>Paiement intégral enregistré</strong><p>Montant : <?= e(money($row['montant_paye'])) ?><br>Date : <?= e(dateFr($row['date_paiement'])) ?></p></div>
<?php else: ?><p><?= $row['date_paiement'] ? 'Facture acquittée.' : 'Paiement non enregistré.' ?></p><?php endif ?>
<?php if ($row['date_retour']): ?><h2>Retour</h2><p>Restitué le <?= e(dateFr($row['date_retour'])) ?> · Maintenance : <?= (int) $row['retour_maintenance'] ?> · Endommagé : <?= (int) $row['retour_endommage'] ?></p><p class="preline"><?= e($row['remarques_retour']) ?></p><?php endif ?>
<p class="muted">Montants de démonstration, sans calcul de taxe. Document généré à partir des données sauvegardées.</p>
</body></html>
