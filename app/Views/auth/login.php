<div class="auth panel">
    <h1>Connexion</h1><p class="muted">Retrouvez vos locations et votre espace de gestion.</p>
    <?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif ?>
    <form method="post" class="stack" data-validate="login" novalidate>
        <?= csrf() ?>
        <?php field('email','Adresse e-mail',$_POST['email'] ?? '','email'); ?>
        <label>Mot de passe<input type="password" name="mot_de_passe" autocomplete="current-password"></label>
        <p class="form-error" role="alert"></p><button>Se connecter</button>
    </form>
    <p>Pas encore de compte ? <a href="<?= e(url('inscription')) ?>">S’inscrire</a></p>
</div>
