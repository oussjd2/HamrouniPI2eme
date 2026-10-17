<a class="back" href="<?= e(url('gestion',['entite'=>$entity])) ?>">Retour à la liste</a><div class="page-heading"><h1><?= e($title) ?></h1></div>
<?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif ?>
<form method="post" class="panel form-grid" data-validate="<?= e($entity) ?>" novalidate>
<?= csrf() ?>
<?php if ($entity==='utilisateurs'): ?>
    <?php field('nom','Nom',$row['nom'] ?? ''); field('prenom','Prénom',$row['prenom'] ?? ''); field('email','Adresse e-mail',$row['email'] ?? '','email'); ?>
    <label>Mot de passe<input type="password" name="mot_de_passe" autocomplete="new-password"><small><?= $id ? 'Laisser vide pour conserver le mot de passe. Sinon : 8 à 72 caractères.' : 'Entre 8 et 72 caractères.' ?></small></label>
    <?php if (isRole('inventaire')): ?><label>Rôle<select name="role"><?php foreach (['client','agent','inventaire'] as $role): ?><option value="<?= e($role) ?>" <?= ($row['role'] ?? 'client')===$role ? 'selected' : '' ?>><?= e(label($role)) ?></option><?php endforeach ?></select></label><?php endif ?>
<?php elseif ($entity==='categories'): ?>
    <?php field('nom','Nom',$row['nom'] ?? ''); ?><label class="wide">Description<textarea name="description" rows="4"><?= e($row['description'] ?? '') ?></textarea></label>
<?php else: ?>
    <?php field('nom','Nom',$row['nom'] ?? ''); ?>
    <label>Catégorie<select name="categorie_id"><option value="">Choisir une catégorie</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) ($row['categorie_id'] ?? '')===(string) $category['id'] ? 'selected' : '' ?>><?= e($category['nom']) ?></option><?php endforeach ?></select></label>
    <label class="wide">Description<textarea name="description" rows="4"><?= e($row['description'] ?? '') ?></textarea></label>
    <?php field('tarif_journalier','Tarif journalier',$row['tarif_journalier'] ?? '','text','Montant positif, deux décimales maximum.'); field('quantite_totale','Stock total',$row['quantite_totale'] ?? 0); field('quantite_maintenance','Quantité en maintenance',$row['quantite_maintenance'] ?? 0); field('quantite_endommagee','Quantité endommagée',$row['quantite_endommagee'] ?? 0); field('seuil_alerte','Seuil d’alerte',$row['seuil_alerte'] ?? 0); ?>
    <p class="wide muted">Pour remettre des unités réparées en circulation, diminuez leur quantité en maintenance ou endommagée. Le stock total inclut tous les états.</p>
<?php endif ?>
<p class="form-error wide" role="alert"></p><div class="actions wide"><button>Enregistrer</button><a href="<?= e(url('gestion',['entite'=>$entity])) ?>">Annuler</a></div>
</form>
