<!doctype html>
<html lang="fr" data-currency="<?= e($config['currency'] ?? 'TND') ?>">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Locamat') ?> — Locamat</title>
    <link rel="stylesheet" href="assets/app.css"><script src="assets/app.js" defer></script>
</head>
<body>
<a class="skip" href="#contenu">Aller au contenu</a>
<header class="topbar">
    <a class="brand" href="<?= e(url()) ?>">Locamat<span>Location d’équipements</span></a>
    <div class="account">
        <?php if ($current=user()): ?>
            <span><?= e($current['prenom'].' '.$current['nom']) ?><small><?= e(label($current['role'])) ?></small></span>
            <form method="post" action="<?= e(url('deconnexion')) ?>"><?= csrf() ?><button class="secondary">Déconnexion</button></form>
        <?php else: ?>
            <a href="<?= e(url('connexion')) ?>">Connexion</a><a class="button" href="<?= e(url('inscription')) ?>">Créer un compte</a>
        <?php endif ?>
    </div>
</header>
<div class="shell">
    <nav class="sidebar" aria-label="Navigation principale">
        <?php
        $active=$_GET['r'] ?? 'catalogue'; $entityActive=$_GET['entite'] ?? '';
        $nav=[['catalogue',[],'Catalogue']];
        if ($current) { $nav[]=['locations',[],$current['role']==='client' ? 'Mes locations' : 'Locations']; }
        if (isRole('inventaire')) { $nav[]=['gestion',['entite'=>'equipements'],'Équipements']; $nav[]=['gestion',['entite'=>'categories'],'Catégories']; }
        if (isRole('inventaire','agent')) { $nav[]=['gestion',['entite'=>'utilisateurs'],isRole('agent') ? 'Clients' : 'Utilisateurs']; }
        foreach ($nav as [$r,$params,$text]):
            $selected=$r==='gestion' ? str_starts_with((string) $active,'gestion') && $entityActive===$params['entite'] : ($r==='locations' ? str_starts_with((string) $active,'location') : in_array($active,['catalogue','equipement'],true));
        ?>
            <a <?= $selected ? 'aria-current="page"' : '' ?> href="<?= e(url($r,$params)) ?>"><?= e($text) ?></a>
        <?php endforeach ?>
        <p class="nav-note">Un catalogue commun.<br>Des actions adaptées à votre rôle.</p>
    </nav>
    <main id="contenu">
        <?php if ($notice=$_SESSION['flash'] ?? null): unset($_SESSION['flash']); ?>
            <div role="status" class="notice <?= e($notice['type']) ?>"><?= e($notice['message']) ?></div>
        <?php endif ?>
        <?= $content ?>
        <footer>Locamat · Projet Technologies Web · PHP / MVC / PDO</footer>
    </main>
</div>
</body>
</html>
