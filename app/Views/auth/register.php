<div class="auth panel"><h1>Créer un compte client</h1>
    <?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif ?>
    <form method="post" class="stack" data-validate="register" novalidate>
        <?= csrf() ?>
        <?php field('nom','Nom',$_POST['nom'] ?? ''); field('prenom','Prénom',$_POST['prenom'] ?? ''); field('email','Adresse e-mail',$_POST['email'] ?? '','email'); ?>
        <label>Mot de passe<input type="password" name="mot_de_passe" autocomplete="new-password"><small>Entre 8 et 72 caractères.</small></label>
        <p class="form-error" role="alert"></p><button>Créer mon compte</button>
    </form>
    <p>Déjà inscrit ? <a href="<?= e(url('connexion')) ?>">Se connecter</a></p>
</div>
