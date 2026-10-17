<form method="get" class="filters panel" novalidate>
    <input type="hidden" name="r" value="<?= isset($entity) ? 'gestion' : 'catalogue' ?>">
    <?php if (isset($entity)): ?><input type="hidden" name="entite" value="equipements"><?php endif ?>
    <?php field('q','Recherche',$_GET['q'] ?? ''); ?>
    <label>Catégorie<select name="categorie_id"><option value="">Toutes</option>
        <?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) ($_GET['categorie_id'] ?? '')===(string) $category['id'] ? 'selected' : '' ?>><?= e($category['nom']) ?></option><?php endforeach ?>
    </select></label>
    <label>État<select name="etat"><option value="">Tous</option><?php foreach (['disponible','location','maintenance','endommage'] as $state): ?><option value="<?= e($state) ?>" <?= ($_GET['etat'] ?? '')===$state ? 'selected' : '' ?>><?= e(label($state)) ?></option><?php endforeach ?></select></label>
    <?php field('prix_min','Tarif minimum',$_GET['prix_min'] ?? '','text'); field('prix_max','Tarif maximum',$_GET['prix_max'] ?? '','text'); ?>
    <?php field('date_debut','Disponible du',$_GET['date_debut'] ?? '','date'); field('date_fin','Au',$_GET['date_fin'] ?? '','date'); ?>
    <?php if (isset($entity)): ?><label class="check"><input type="checkbox" name="alertes" value="1" <?= !empty($_GET['alertes']) ? 'checked' : '' ?>> Stock en alerte</label><?php endif ?>
    <div class="actions"><button>Rechercher</button><a href="<?= e(isset($entity) ? url('gestion',['entite'=>$entity]) : url()) ?>">Réinitialiser</a></div>
</form>
