<div class="toolbar">
    <h1>Usuario</h1>
    <a class="btn" href="<?= url('usuario/create') ?>">Nuevo</a>
</div>

<?php if (empty($items)): ?>
    <p>No hay registros.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
            <th>Id</th>
            <th>Nombre</th>
            <th>Login</th>
            <th>Password</th>
            <th>Email</th>
            <th>Created at</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
            <td><?= e($item->id) ?></td>
            <td><?= e($item->name) ?></td>
            <td><?= e($item->login) ?></td>
            <td><?= e($item->password) ?></td>
            <td><?= e($item->email) ?></td>
            <td><?= e($item->created_at) ?></td>
                <td class="actions">
                    <a href="<?= url('usuario/edit/' . $item->id) ?>">Editar</a>

                    <form method="post"
                          action="<?= url('usuario/delete/' . $item->id) ?>"
                          onsubmit="return confirm('Eliminar este registro?')">
                        <button class="link danger" type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
