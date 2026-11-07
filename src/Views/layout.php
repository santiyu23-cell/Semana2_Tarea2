<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($title ?? 'App') ?> - <?= e(\App\Core\Env::get('APP_NAME', 'App')) ?></title>

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, Arial, sans-serif; background: #f4f5f7; color: #222; }
        nav { background: #6de28a; padding: 12px 24px; }
        nav a { color: #fff; margin-right: 18px; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        main { max-width: 960px; margin: 24px auto; background: #fff; padding: 24px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; }
        .btn, button.btn { background: #2563eb; color: #fff; border: 0; padding: 8px 14px; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: 14px; }
        .actions { display: flex; gap: 12px; align-items: center; }
        .actions form { margin: 0; }
        button.link { background: none; border: 0; color: #2563eb; cursor: pointer; padding: 0; font-size: 14px; }
        button.danger { color: #dc2626; }
        .field { margin-bottom: 14px; display: flex; flex-direction: column; gap: 4px; }
        .field input[type=checkbox] { width: 18px; height: 18px; }
        input, select, textarea { padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .error { color: #dc2626; }
        .buttons { display: flex; gap: 16px; align-items: center; margin-top: 20px; }
        .flash { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; }
        .flash.ok { background: #dcfce7; }
        .flash.error { background: #fee2e2; }
    </style>
</head>
<body>
<nav>
    <a href="<?= url('') ?>">Inicio</a>
    <a href="<?= url('medicina') ?>">Medicina</a>
    <a href="<?= url('usuario') ?>">Usuario</a>
    <a href="<?= url('venta') ?>">Venta</a>
    <!-- MENU -->
</nav>

<main>
    <?php if ($flash = \App\Core\Flash::pull()): ?>
        <div class="flash <?= e($flash['t']) ?>">
            <?= e($flash['m']) ?>
        </div>
    <?php endif; ?>

    <?= $content ?>
</main>
</body>
</html>
