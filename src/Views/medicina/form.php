<h1>INGRESE LA MEDICINA</h1>

<form method="post" action="<?= url($action) ?>">
    <div class="field">
        <label for="name">Nombre</label>
        <input type="text" id="name" name="name" maxlength="120" value="<?= e($entity->name) ?>" required>
        <?php if (isset($errors['name'])): ?>
            <small class="error"><?= e($errors['name']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="description">Detalle</label>
        <textarea id="description" name="description" rows="4"><?= e($entity->description) ?></textarea>
        <?php if (isset($errors['description'])): ?>
            <small class="error"><?= e($errors['description']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="price">Precio</label>
        <input type="number" id="price" name="price" step="0.01" value="<?= e($entity->price) ?>">
        <?php if (isset($errors['price'])): ?>
            <small class="error"><?= e($errors['price']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="stock">Stock</label>
        <input type="number" id="stock" name="stock" step="1" value="<?= e($entity->stock) ?>">
        <?php if (isset($errors['stock'])): ?>
            <small class="error"><?= e($errors['stock']) ?></small>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="active">Activo</label>
        <input type="checkbox" id="active" name="active" value="1" <?= $entity->active ? 'checked' : '' ?>>
        <?php if (isset($errors['active'])): ?>
            <small class="error"><?= e($errors['active']) ?></small>
        <?php endif; ?>
    </div>

    <div class="buttons">
        <button class="btn" type="submit">Guardar</button>
        <a href="<?= url('medicina') ?>">Cancelar</a>
    </div>
</form>
