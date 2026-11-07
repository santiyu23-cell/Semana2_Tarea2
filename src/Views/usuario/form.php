<h1><?= e($title) ?></h1>

<form method="post" action="<?= url($action) ?>">
    <div class="field">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" maxlength="120" value="<?= e($entity->name) ?>" required>
        <?php if (isset($errors['name'])): ?>
            <small class="error"><?= e($errors['name']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="login">Login</label>
        <input type="text" id="login" name="login" maxlength="120" value="<?= e($entity->login) ?>" required>
        <?php if (isset($errors['login'])): ?>
            <small class="error"><?= e($errors['login']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <input type="text" id="password" name="password" maxlength="255" value="<?= e($entity->password) ?>" required>
        <?php if (isset($errors['password'])): ?>
            <small class="error"><?= e($errors['password']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" maxlength="120" value="<?= e($entity->email) ?>" required>
        <?php if (isset($errors['email'])): ?>
            <small class="error"><?= e($errors['email']) ?></small>
        <?php endif; ?>
    </div>

    <div class="buttons">
        <button class="btn" type="submit">Guardar</button>
        <a href="<?= url('usuario') ?>">Cancelar</a>
    </div>
</form>
