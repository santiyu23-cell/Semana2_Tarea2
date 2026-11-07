<h1><?= e($title) ?></h1>

<form method="post" action="<?= url($action) ?>">
    <div class="field">
        <label for="user_id">User id</label>
        <input type="number" id="user_id" name="user_id" step="1" value="<?= e($entity->user_id) ?>">
        <?php if (isset($errors['user_id'])): ?>
            <small class="error"><?= e($errors['user_id']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="total">Total</label>
        <input type="number" id="total" name="total" step="0.01" value="<?= e($entity->total) ?>">
        <?php if (isset($errors['total'])): ?>
            <small class="error"><?= e($errors['total']) ?></small>
        <?php endif; ?>
    </div>

    <div class="buttons">
        <button class="btn" type="submit">Guardar</button>
        <a href="<?= url('venta') ?>">Cancelar</a>
    </div>
</form>
