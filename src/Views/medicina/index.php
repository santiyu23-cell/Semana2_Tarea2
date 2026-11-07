<div class="toolbar">
    <h1>Medicina</h1>
    <a class="btn" href="<?= url('medicina/create') ?>">Nuevo</a>
</div>

<?php if (empty($items)): ?>
    <p>No hay registros.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
            <th>Id</th>
            <th>Nombre</th>
            <th>Detalle</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Activo</th>
            <th>Creado a</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
            <td><?= e($item->id) ?></td>
            <td><?= e($item->name) ?></td>
            <td><?= e(mb_strimwidth((string) $item->description, 0, 60, '...')) ?></td>
            <td><?= e($item->price) ?></td>
            <td><?= e($item->stock) ?></td>
            <td><?= $item->active === null ? '-' : ($item->active ? 'Si' : 'No') ?></td>
            <td><?= e($item->created_at) ?></td>
                <td class="actions">
                    <a href="<?= url('medicina/edit/' . $item->id) ?>">Editar</a>

                    <form method="post"
                          action="<?= url('medicina/delete/' . $item->id) ?>"
                          onsubmit="return confirm('Eliminar este registro?')">
                        <button class="link danger" type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
