<div class="toolbar">
    <h1>Venta</h1>
    <a class="btn" href="<?= url('venta/create') ?>">Nuevo</a>
</div>

<?php if (empty($items)): ?>
    <p>No hay registros.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
            <th>Id</th>
            <th>User id</th>
            <th>Total</th>
            <th>Created at</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
            <td><?= e($item->id) ?></td>
            <td><?= e($item->user_id) ?></td>
            <td><?= e($item->total) ?></td>
            <td><?= e($item->created_at) ?></td>
                <td class="actions">
                    <a href="<?= url('venta/edit/' . $item->id) ?>">Editar</a>

                    <form method="post"
                          action="<?= url('venta/delete/' . $item->id) ?>"
                          onsubmit="return confirm('Eliminar este registro?')">
                        <button class="link danger" type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
