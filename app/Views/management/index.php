<div class="page-heading"><h1><?= e($title) ?></h1><a class="button" href="<?= e(url('gestion-form',['entite'=>$entity])) ?>">Ajouter <?= $entity==='equipements' ? 'un équipement' : ($entity==='categories' ? 'une catégorie' : 'un utilisateur') ?></a></div>
<?php if ($entity==='equipements'): require ROOT . '/app/Views/catalogue/filters.php'; ?><p class="muted">Disponibilité : période choisie, aujourd’hui par défaut. Les états peuvent coexister pour un même produit.</p><?php endif ?>
<div class="table-wrap"><table><thead><tr>
<?php if ($entity==='utilisateurs'): ?><th>Nom</th><th>E-mail</th><th>Rôle</th>
<?php elseif ($entity==='categories'): ?><th>Nom</th><th>Description</th><th>Équipements</th>
<?php else: ?><th>Équipement</th><th>Catégorie</th><th>Tarif / jour</th><th>Stock total</th><th>Disponible</th><th>États</th><?php endif ?>
<th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr>
<?php if ($entity==='utilisateurs'): ?><td><strong><?= e($row['prenom'].' '.$row['nom']) ?></strong></td><td><?= e($row['email']) ?></td><td><?= e(label($row['role'])) ?></td>
<?php elseif ($entity==='categories'): ?><td><strong><?= e($row['nom']) ?></strong></td><td><?= e(mb_strimwidth($row['description'],0,100,'…')) ?></td><td><?= (int) $row['nombre'] ?></td>
<?php else: ?><td><strong><?= e($row['nom']) ?></strong><small>#<?= (int) $row['id'] ?></small></td><td><?= e($row['categorie']) ?></td><td><?= e(money($row['tarif_journalier'])) ?></td><td><?= (int) $row['quantite_totale'] ?></td><td><?= (int) $row['disponible'] ?><?php if ($row['disponible']<=$row['seuil_alerte']): ?><small class="warning">Seuil d’alerte atteint (<?= (int) $row['seuil_alerte'] ?>)</small><?php endif ?></td><td><small>En location : <?= (int) $row['en_location'] ?><br>Maintenance : <?= (int) $row['quantite_maintenance'] ?><br>Endommagé : <?= (int) $row['quantite_endommagee'] ?></small></td><?php endif ?>
<td><div class="actions"><a href="<?= e(url('gestion-detail',['entite'=>$entity,'id'=>$row['id']])) ?>">Voir</a><a href="<?= e(url('gestion-form',['entite'=>$entity,'id'=>$row['id']])) ?>">Modifier</a><form method="post" action="<?= e(url('gestion-delete',['entite'=>$entity])) ?>" data-confirm="Supprimer cet élément ? Les éléments liés à l’historique sont protégés."><?= csrf() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="danger text-button">Supprimer</button></form></div></td>
</tr><?php endforeach ?>
<?php if (!$rows): ?><tr><td colspan="7" class="empty">Aucun élément à afficher.</td></tr><?php endif ?>
</tbody></table></div>
