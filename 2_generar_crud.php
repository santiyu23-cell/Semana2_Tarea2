<?php
declare(strict_types=1);

/**
 * PASO 3 - GENERADOR CRUD PARA POSTGRESQL
 *
 * Uso:
 *   php 2_generar_crud.php                              (CRUD de TODAS las tablas)
 *   php 2_generar_crud.php products                     (solo una tabla)
 *   php 2_generar_crud.php products Product --force     (regenera sobreescribiendo)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Recurso no disponible.');
}

if (!is_file(__DIR__ . '/src/bootstrap.php')) {
    exit("Primero ejecuta 1_crear_proyecto.php\n");
}

require __DIR__ . '/src/bootstrap.php';

use App\Core\Database;

// ============================================================
// NAMING
// ============================================================

final class Naming
{
    public static function studly(string $value): string
    {
        return str_replace(
            ' ',
            '',
            ucwords(str_replace(['_', '-'], ' ', $value))
        );
    }

    public static function singular(string $value): string
    {
        return match (true) {
            str_ends_with($value, 'ies') => substr($value, 0, -3) . 'y',
            str_ends_with($value, 'ss') => $value,
            str_ends_with($value, 's') => substr($value, 0, -1),
            default => $value,
        };
    }

    public static function label(string $value): string
    {
        return ucfirst(str_replace('_', ' ', $value));
    }
}

// ============================================================
// COLUMN
// ============================================================

final class Column
{
    public function __construct(
        public string $name,
        public string $phpType,
        public bool $nullable,
        public bool $required,
        public bool $isPk,
        public bool $auto,
        public string $input,
        public ?int $maxLength,
        public string $step,
        public array $options = [],
    ) {
    }

    public function label(): string
    {
        return Naming::label($this->name);
    }

    public static function fromRow(array $row): self
    {
        $name = (string) $row['column_name'];

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new RuntimeException("Nombre de columna no soportado: $name");
        }

        $type = strtolower((string) $row['data_type']);
        $default = $row['column_default'] ?? null;

        $isPk = ($row['is_pk'] ?? 'NO') === 'YES';
        $nullable = ($row['is_nullable'] ?? 'YES') === 'YES';

        // Autogenerada: SERIAL/BIGSERIAL (nextval) o IDENTITY.
        $auto = (is_string($default)
                && str_contains(strtolower($default), 'nextval('))
            || ($row['is_identity'] ?? 'NO') === 'YES';

        // created_at / updated_at con DEFAULT CURRENT_TIMESTAMP los llena la BD.
        if (
            !$auto
            && is_string($default)
            && preg_match('/^(timestamp|date|time)/', $type)
            && preg_match('/current_(timestamp|date|time)|now\(\)|localtimestamp/i', $default)
        ) {
            $auto = true;
        }

        $php = 'string';
        $input = 'text';
        $max = null;
        $step = 'any';

        switch ($type) {
            case 'boolean':
                $php = 'bool';
                $input = 'checkbox';
                break;

            case 'smallint':
            case 'integer':
            case 'bigint':
                $php = 'int';
                $input = 'number';
                $step = '1';
                break;

            case 'numeric':
            case 'decimal':
            case 'real':
            case 'double precision':
                $php = 'float';
                $input = 'number';
                $step = 'any';

                if (
                    isset($row['numeric_scale'])
                    && $row['numeric_scale'] !== null
                    && (int) $row['numeric_scale'] > 0
                ) {
                    $scale = (int) $row['numeric_scale'];
                    $step = '0.' . str_repeat('0', $scale - 1) . '1';
                }
                break;

            case 'date':
                $input = 'date';
                break;

            case 'timestamp without time zone':
            case 'timestamp with time zone':
                $input = 'datetime-local';
                break;

            case 'time without time zone':
            case 'time with time zone':
                $input = 'time';
                break;

            case 'text':
            case 'json':
            case 'jsonb':
                $input = 'textarea';
                break;

            case 'character varying':
            case 'character':
                $max = isset($row['character_maximum_length'])
                    ? (int) $row['character_maximum_length']
                    : null;

                if (str_contains(strtolower($name), 'email')) {
                    $input = 'email';
                }
                break;

            default:
                $input = 'text';
                break;
        }

        $required = !$nullable
            && $default === null
            && !$auto
            && $php === 'string';

        return new self(
            $name,
            $php,
            $nullable,
            $required,
            $isPk,
            $auto,
            $input,
            $max,
            $step
        );
    }
}

// ============================================================
// TABLE INSPECTOR
// ============================================================

final class TableInspector
{
    /** Tablas internas que nunca se generan. */
    private const EXCLUDED = ['schema_migrations', 'migrations'];

    public function __construct(private PDO $pdo)
    {
    }

    public function tables(): array
    {
        $sql = "
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = current_schema()
              AND table_type = 'BASE TABLE'
            ORDER BY table_name
        ";

        $all = $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_diff($all, self::EXCLUDED));
    }

    private function hasIdentityColumn(): bool
    {
        // is_identity existe desde PostgreSQL 10.
        $st = $this->pdo->query("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = 'information_schema'
              AND table_name = 'columns'
              AND column_name = 'is_identity'
        ");

        return (bool) $st->fetchColumn();
    }

    public function columns(string $table): array
    {
        if (!in_array($table, $this->tables(), true)) {
            throw new RuntimeException(
                "La tabla '$table' no existe en el esquema actual."
            );
        }

        $identity = $this->hasIdentityColumn()
            ? 'c.is_identity,'
            : "'NO' AS is_identity,";

        $sql = "
            SELECT
                c.column_name,
                c.data_type,
                c.column_default,
                c.is_nullable,
                c.character_maximum_length,
                c.numeric_scale,
                $identity
                CASE
                    WHEN pk.column_name IS NOT NULL THEN 'YES'
                    ELSE 'NO'
                END AS is_pk
            FROM information_schema.columns c
            LEFT JOIN (
                SELECT kcu.column_name
                FROM information_schema.table_constraints tc
                INNER JOIN information_schema.key_column_usage kcu
                    ON tc.constraint_catalog = kcu.constraint_catalog
                   AND tc.constraint_schema = kcu.constraint_schema
                   AND tc.constraint_name = kcu.constraint_name
                   AND tc.table_schema = kcu.table_schema
                   AND tc.table_name = kcu.table_name
                WHERE tc.constraint_type = 'PRIMARY KEY'
                  AND tc.table_schema = current_schema()
                  AND tc.table_name = :table_pk
            ) pk ON pk.column_name = c.column_name
            WHERE c.table_schema = current_schema()
              AND c.table_name = :table_columns
            ORDER BY c.ordinal_position
        ";

        $statement = $this->pdo->prepare($sql);

        $statement->execute([
            'table_pk' => $table,
            'table_columns' => $table,
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $columns = array_map(
            [Column::class, 'fromRow'],
            $rows
        );

        $pks = array_values(array_filter(
            $columns,
            fn(Column $column) => $column->isPk
        ));

        if (count($pks) !== 1) {
            throw new RuntimeException(
                'La tabla necesita una clave primaria simple (una sola columna).'
            );
        }

        if (!$pks[0]->auto || $pks[0]->phpType !== 'int') {
            throw new RuntimeException(
                'La clave primaria debe ser entera y autogenerada '
                . '(SERIAL, BIGSERIAL o GENERATED ... AS IDENTITY).'
            );
        }

        return $columns;
    }
}

// ============================================================
// BLUEPRINT
// ============================================================

final class Blueprint
{
    public function __construct(
        public readonly string $table,
        public readonly string $entity,
        public readonly array $columns,
    ) {
    }

    public function pk(): Column
    {
        foreach ($this->columns as $column) {
            if ($column->isPk) {
                return $column;
            }
        }

        throw new RuntimeException('La tabla no tiene clave primaria.');
    }

    public function writable(): array
    {
        return array_values(array_filter(
            $this->columns,
            fn(Column $column) => !$column->auto
        ));
    }

    public function vars(): array
    {
        return [
            'ENTITY' => $this->entity,
            'TABLE' => $this->table,
            'PK' => $this->pk()->name,
            'CONTROLLER' => Naming::studly($this->table) . 'Controller',
            'LABEL' => Naming::label(Naming::singular($this->table)),
            'TITLE' => Naming::label($this->table),
        ];
    }
}

// ============================================================
// BUILDERS
// ============================================================

interface ArtifactBuilder
{
    public function path(): string;

    public function render(): string;
}

abstract class TemplateBuilder implements ArtifactBuilder
{
    public function __construct(protected Blueprint $b)
    {
    }

    protected function fill(string $template, array $extra = []): string
    {
        $map = [];

        foreach ($this->b->vars() + $extra as $key => $value) {
            $map['{{' . $key . '}}'] = (string) $value;
        }

        return strtr($template, $map);
    }
}

// ============================================================
// ENTITY BUILDER
// ============================================================

final class EntityBuilder extends TemplateBuilder
{
    public function path(): string
    {
        return "src/Entities/{$this->b->entity}.php";
    }

    public function render(): string
    {
        $params = [];
        $from = [];
        $to = [];

        foreach ($this->b->columns as $column) {
            $params[] = '        ' . $this->param($column);

            // Los argumentos nombrados de PHP no llevan comillas.
            $from[] = '            '
                . $column->name
                . ': '
                . $this->cast($column)
                . ',';

            $value = $column->phpType === 'bool'
                ? "(int) \$this->{$column->name}"
                : "\$this->{$column->name}";

            $to[] = '            '
                . var_export($column->name, true)
                . " => $value,";
        }

        return $this->fill($this->template(), [
            'PARAMS' => implode("\n", $params),
            'FROM' => implode("\n", $from),
            'TO' => implode("\n", $to),
        ]);
    }

    private function param(Column $column): string
    {
        if ($column->isPk || $column->nullable) {
            return "public ?{$column->phpType} \${$column->name} = null,";
        }

        return "public {$column->phpType} \${$column->name} = "
            . $this->defaultValue($column)
            . ',';
    }

    private function defaultValue(Column $column): string
    {
        return match ($column->phpType) {
            'int' => '0',
            'float' => '0.0',
            'bool' => 'false',
            default => "''",
        };
    }

    /**
     * Expresion PHP que convierte $d['columna'] al tipo de la propiedad.
     * Los ternarios van entre parentesis para que no se rompan
     * dentro de un argumento nombrado (columna: expr,).
     */
    private function cast(Column $column): string
    {
        $name = $column->name;
        $key = "\$d['$name']";

        if ($column->phpType === 'bool') {
            if ($column->nullable || $column->isPk) {
                return "(array_key_exists('$name', \$d) "
                    . "&& \$d['$name'] !== null "
                    . "&& \$d['$name'] !== '' "
                    . "? in_array(strtolower((string) \$d['$name']), "
                    . "['1', 't', 'true', 'yes', 'on'], true) "
                    . ': null)';
            }

            return "in_array(strtolower((string) (\$d['$name'] ?? '')), "
                . "['1', 't', 'true', 'yes', 'on'], true)";
        }

        $cast = match ($column->phpType) {
            'int' => '(int) ',
            'float' => '(float) ',
            default => '(string) ',
        };

        if ($column->isPk || $column->nullable) {
            return "(isset($key) && $key !== '' ? $cast$key : null)";
        }

        return "$cast($key ?? " . $this->defaultValue($column) . ')';
    }

    private function template(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Entities;

/** Una fila de la tabla {{TABLE}}. */
final class {{ENTITY}}
{
    public function __construct(
{{PARAMS}}
    ) {
    }

    public static function fromArray(array $d): self
    {
        return new self(
{{FROM}}
        );
    }

    public function toArray(): array
    {
        return [
{{TO}}
        ];
    }
}

EOT;
    }
}

// ============================================================
// REPOSITORY INTERFACE BUILDER
// ============================================================

final class RepositoryInterfaceBuilder extends TemplateBuilder
{
    public function path(): string
    {
        return "src/Repositories/{$this->b->entity}RepositoryInterface.php";
    }

    public function render(): string
    {
        return $this->fill(<<<'EOT'
<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Entities\{{ENTITY}};

interface {{ENTITY}}RepositoryInterface
{
    /** @return {{ENTITY}}[] */
    public function all(): array;

    public function find(int $id): ?{{ENTITY}};

    public function create({{ENTITY}} $entity): int;

    public function update({{ENTITY}} $entity): void;

    public function delete(int $id): void;
}

EOT);
    }
}

// ============================================================
// REPOSITORY BUILDER
// ============================================================

final class RepositoryBuilder extends TemplateBuilder
{
    public function path(): string
    {
        return "src/Repositories/{$this->b->entity}Repository.php";
    }

    public function render(): string
    {
        $names = array_map(
            fn(Column $column) => $column->name,
            $this->b->writable()
        );

        $quotedNames = array_map(
            fn(string $name) => '"' . $name . '"',
            $names
        );

        $placeholders = array_map(
            fn(string $name) => ':' . $name,
            $names
        );

        $sets = array_map(
            fn(string $name) => '"' . $name . '" = :' . $name,
            $names
        );

        $pk = $this->b->pk()->name;

        return $this->fill($this->template(), [
            'WRITABLE' => implode(
                ', ',
                array_map(fn(string $name) => var_export($name, true), $names)
            ),
            'COLS' => implode(', ', $quotedNames),
            'VALS' => implode(', ', $placeholders),
            'SETS' => implode(', ', $sets),
            'RETURNING' => '"' . $pk . '"',
        ]);
    }

    private function template(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Entities\{{ENTITY}};
use PDO;

final class {{ENTITY}}Repository implements {{ENTITY}}RepositoryInterface
{
    private const WRITABLE = [{{WRITABLE}}];

    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $rows = $this->pdo
            ->query('SELECT * FROM "{{TABLE}}" ORDER BY "{{PK}}" DESC')
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            [{{ENTITY}}::class, 'fromArray'],
            $rows
        );
    }

    public function find(int $id): ?{{ENTITY}}
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM "{{TABLE}}" WHERE "{{PK}}" = :id'
        );

        $st->execute(['id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ? {{ENTITY}}::fromArray($row) : null;
    }

    public function create({{ENTITY}} $entity): int
    {
        $sql = '
            INSERT INTO "{{TABLE}}" ({{COLS}})
            VALUES ({{VALS}})
            RETURNING {{RETURNING}}
        ';

        $st = $this->pdo->prepare($sql);
        $st->execute($this->data($entity));

        return (int) $st->fetchColumn();
    }

    public function update({{ENTITY}} $entity): void
    {
        $sql = '
            UPDATE "{{TABLE}}"
            SET {{SETS}}
            WHERE "{{PK}}" = :__pk
        ';

        $st = $this->pdo->prepare($sql);

        $st->execute(
            $this->data($entity) + [
                '__pk' => $entity->{{PK}}
            ]
        );
    }

    public function delete(int $id): void
    {
        $st = $this->pdo->prepare(
            'DELETE FROM "{{TABLE}}" WHERE "{{PK}}" = :id'
        );

        $st->execute(['id' => $id]);
    }

    private function data({{ENTITY}} $entity): array
    {
        return array_intersect_key(
            $entity->toArray(),
            array_flip(self::WRITABLE)
        );
    }
}

EOT;
    }
}

// ============================================================
// SERVICE BUILDER
// ============================================================

final class ServiceBuilder extends TemplateBuilder
{
    public function path(): string
    {
        return "src/Services/{$this->b->entity}Service.php";
    }

    public function render(): string
    {
        $rules = [];

        foreach ($this->b->writable() as $column) {
            foreach ($this->rulesFor($column) as $rule) {
                $rules[] = $rule;
            }
        }

        return $this->fill(
            $this->template(),
            ['RULES' => implode("\n", $rules)]
        );
    }

    private function rulesFor(Column $column): array
    {
        $name = $column->name;
        $label = addslashes($column->label());
        $rules = [];

        if ($column->required && $column->phpType === 'string') {
            $rules[] = "        if (trim((string) \$e->$name) === '') {\n"
                . "            \$errors['$name'] = '$label es obligatorio.';\n"
                . "        }";
        }

        if ($column->maxLength !== null) {
            $rules[] = "        if (mb_strlen((string) \$e->$name) > {$column->maxLength}) {\n"
                . "            \$errors['$name'] = '$label admite maximo {$column->maxLength} caracteres.';\n"
                . "        }";
        }

        if ($column->input === 'email') {
            $rules[] = "        if ((string) \$e->$name !== '' "
                . "&& !filter_var(\$e->$name, FILTER_VALIDATE_EMAIL)) {\n"
                . "            \$errors['$name'] = '$label no es un correo valido.';\n"
                . "        }";
        }

        return $rules;
    }

    private function template(): string
    {
        return <<<'EOT'
<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\NotFoundException;
use App\Entities\{{ENTITY}};
use App\Repositories\{{ENTITY}}RepositoryInterface;

final class {{ENTITY}}Service
{
    public function __construct(
        private {{ENTITY}}RepositoryInterface $repository
    ) {
    }

    public function list(): array
    {
        return $this->repository->all();
    }

    public function get(int $id): {{ENTITY}}
    {
        return $this->repository->find($id)
            ?? throw new NotFoundException();
    }

    public function validate({{ENTITY}} $e): array
    {
        $errors = [];

{{RULES}}

        return $errors;
    }

    public function create({{ENTITY}} $e): int
    {
        return $this->repository->create($e);
    }

    public function update({{ENTITY}} $e): void
    {
        $this->repository->update($e);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}

EOT;
    }
}

// ============================================================
// CONTROLLER BUILDER
// ============================================================

final class ControllerBuilder extends TemplateBuilder
{
    public function path(): string
    {
        return 'src/Controllers/'
            . Naming::studly($this->b->table)
            . 'Controller.php';
    }

    public function render(): string
    {
        return $this->fill(<<<'EOT'
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Entities\{{ENTITY}};
use App\Repositories\{{ENTITY}}Repository;
use App\Services\{{ENTITY}}Service;
use PDOException;

final class {{CONTROLLER}} extends Controller
{
    private {{ENTITY}}Service $service;

    public function __construct(?{{ENTITY}}Service $service = null)
    {
        parent::__construct();

        $this->service = $service
            ?? new {{ENTITY}}Service(
                new {{ENTITY}}Repository(Database::connection())
            );
    }

    public function index(): void
    {
        $this->render(
            '{{TABLE}}/index',
            ['items' => $this->service->list()],
            '{{TITLE}}'
        );
    }

    public function create(): void
    {
        $this->form(new {{ENTITY}}(), [], '{{TABLE}}/store', 'Nuevo {{LABEL}}');
    }

    public function store(): void
    {
        $this->requirePost();

        $entity = {{ENTITY}}::fromArray($this->request->allPost());
        $errors = $this->service->validate($entity);

        if ($errors) {
            $this->form($entity, $errors, '{{TABLE}}/store', 'Nuevo {{LABEL}}');
            return;
        }

        $this->service->create($entity);

        $this->redirect('{{TABLE}}', 'Registro creado correctamente.');
    }

    public function edit(?int $id = null): void
    {
        $entity = $this->service->get((int) $id);

        $this->form(
            $entity,
            [],
            '{{TABLE}}/update/' . $entity->{{PK}},
            'Editar {{LABEL}}'
        );
    }

    public function update(?int $id = null): void
    {
        $this->requirePost();

        $this->service->get((int) $id);

        $entity = {{ENTITY}}::fromArray($this->request->allPost());
        $entity->{{PK}} = (int) $id;

        $errors = $this->service->validate($entity);

        if ($errors) {
            $this->form(
                $entity,
                $errors,
                '{{TABLE}}/update/' . $id,
                'Editar {{LABEL}}'
            );
            return;
        }

        $this->service->update($entity);

        $this->redirect('{{TABLE}}', 'Registro actualizado correctamente.');
    }

    public function delete(?int $id = null): void
    {
        $this->requirePost();

        $this->service->get((int) $id);

        try {
            $this->service->delete((int) $id);
        } catch (PDOException) {
            $this->redirect(
                '{{TABLE}}',
                'No se pudo eliminar: el registro esta en uso.',
                'error'
            );
        }

        $this->redirect('{{TABLE}}', 'Registro eliminado.');
    }

    private function form(
        {{ENTITY}} $entity,
        array $errors,
        string $action,
        string $title
    ): void {
        $this->render(
            '{{TABLE}}/form',
            compact('entity', 'errors', 'action'),
            $title
        );
    }
}

EOT);
    }
}

// ============================================================
// INDEX VIEW BUILDER
// ============================================================

final class IndexViewBuilder extends TemplateBuilder
{
    public function path(): string
    {
        return "src/Views/{$this->b->table}/index.php";
    }

    public function render(): string
    {
        $headers = [];
        $cells = [];

        foreach ($this->b->columns as $column) {
            $headers[] = '            <th>'
                . htmlspecialchars($column->label(), ENT_QUOTES, 'UTF-8')
                . '</th>';

            $cells[] = '            ' . $this->cell($column);
        }

        return $this->fill($this->template(), [
            'TH' => implode("\n", $headers),
            'TD' => implode("\n", $cells),
        ]);
    }

    private function cell(Column $column): string
    {
        $value = "\$item->{$column->name}";

        return match (true) {
            $column->phpType === 'bool' =>
                "<td><?= $value === null ? '-' : ($value ? 'Si' : 'No') ?></td>",

            $column->input === 'textarea' =>
                "<td><?= e(mb_strimwidth((string) $value, 0, 60, '...')) ?></td>",

            default =>
                "<td><?= e($value) ?></td>",
        };
    }

    private function template(): string
    {
        return <<<'EOT'
<div class="toolbar">
    <h1>{{TITLE}}</h1>
    <a class="btn" href="<?= url('{{TABLE}}/create') ?>">Nuevo</a>
</div>

<?php if (empty($items)): ?>
    <p>No hay registros.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
{{TH}}
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
{{TD}}
                <td class="actions">
                    <a href="<?= url('{{TABLE}}/edit/' . $item->{{PK}}) ?>">Editar</a>

                    <form method="post"
                          action="<?= url('{{TABLE}}/delete/' . $item->{{PK}}) ?>"
                          onsubmit="return confirm('Eliminar este registro?')">
                        <button class="link danger" type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

EOT;
    }
}

// ============================================================
// FORM VIEW BUILDER
// ============================================================

final class FormViewBuilder extends TemplateBuilder
{
    public function path(): string
    {
        return "src/Views/{$this->b->table}/form.php";
    }

    public function render(): string
    {
        $fields = [];

        foreach ($this->b->writable() as $column) {
            $fields[] = $this->field($column);
        }

        return $this->fill(
            $this->template(),
            ['FIELDS' => implode("\n\n", $fields)]
        );
    }

    private function field(Column $column): string
    {
        $name = $column->name;
        $value = "\$entity->$name";
        $required = $column->required ? ' required' : '';

        $max = $column->maxLength !== null
            ? " maxlength=\"{$column->maxLength}\""
            : '';

        $label = htmlspecialchars($column->label(), ENT_QUOTES, 'UTF-8');

        $control = match ($column->input) {
            'checkbox' =>
                "<input type=\"checkbox\" id=\"$name\" name=\"$name\" "
                . "value=\"1\" <?= $value ? 'checked' : '' ?>>",

            'textarea' =>
                "<textarea id=\"$name\" name=\"$name\" rows=\"4\"$required>"
                . "<?= e($value) ?></textarea>",

            'datetime-local' =>
                "<input type=\"datetime-local\" id=\"$name\" name=\"$name\" "
                . "value=\"<?= e($value ? str_replace(' ', 'T', substr((string) $value, 0, 16)) : '') ?>\"$required>",

            'date' =>
                "<input type=\"date\" id=\"$name\" name=\"$name\" "
                . "value=\"<?= e($value) ?>\"$required>",

            'time' =>
                "<input type=\"time\" id=\"$name\" name=\"$name\" "
                . "value=\"<?= e($value ? substr((string) $value, 0, 8) : '') ?>\"$required>",

            'number' =>
                "<input type=\"number\" id=\"$name\" name=\"$name\" "
                . "step=\"{$column->step}\" value=\"<?= e($value) ?>\"$required>",

            default =>
                "<input type=\"{$column->input}\" id=\"$name\" name=\"$name\""
                . "$max value=\"<?= e($value) ?>\"$required>",
        };

        return <<<EOT
    <div class="field">
        <label for="$name">$label</label>
        $control
        <?php if (isset(\$errors['$name'])): ?>
            <small class="error"><?= e(\$errors['$name']) ?></small>
        <?php endif; ?>
    </div>
EOT;
    }

    private function template(): string
    {
        return <<<'EOT'
<h1><?= e($title) ?></h1>

<form method="post" action="<?= url($action) ?>">
{{FIELDS}}

    <div class="buttons">
        <button class="btn" type="submit">Guardar</button>
        <a href="<?= url('{{TABLE}}') ?>">Cancelar</a>
    </div>
</form>

EOT;
    }
}

// ============================================================
// FILE WRITER
// ============================================================

final class FileWriter
{
    public function __construct(
        private string $root,
        private bool $force
    ) {
    }

    public function write(string $relativePath, string $content): string
    {
        $fullPath = $this->root . '/' . $relativePath;
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new RuntimeException(
                    "No se pudo crear el directorio: $directory"
                );
            }
        }

        if (file_exists($fullPath) && !$this->force) {
            return "= ya existe (usa --force): $relativePath";
        }

        if (file_put_contents($fullPath, $content) === false) {
            throw new RuntimeException(
                "No se pudo escribir el archivo: $relativePath"
            );
        }

        return "+ generado: $relativePath";
    }
}

// ============================================================
// MENU LINKER
// ============================================================

final class MenuLinker
{
    private const MARKER = '<!-- MENU -->';

    public function __construct(private string $layoutFile)
    {
    }

    public function add(string $table, string $label): string
    {
        if (!is_file($this->layoutFile)) {
            return '! No se encontro src/Views/layout.php.';
        }

        $html = (string) file_get_contents($this->layoutFile);

        $link = '<a href="<?= url('
            . var_export($table, true)
            . ') ?>">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</a>';

        if (str_contains($html, "url('$table')")) {
            return '= el enlace del menu ya existe';
        }

        if (!str_contains($html, self::MARKER)) {
            return "! Agrega manualmente en layout.php: $link";
        }

        $updated = str_replace(
            self::MARKER,
            $link . "\n    " . self::MARKER,
            $html
        );

        if (file_put_contents($this->layoutFile, $updated) === false) {
            return '! No se pudo modificar layout.php.';
        }

        return '+ enlace agregado al menu: src/Views/layout.php';
    }
}

// ============================================================
// CRUD GENERATOR
// ============================================================

final class CrudGenerator
{
    public function __construct(
        private array $builders,
        private FileWriter $writer
    ) {
    }

    public function run(): array
    {
        $log = [];

        foreach ($this->builders as $builder) {
            $log[] = $this->writer->write(
                $builder->path(),
                $builder->render()
            );
        }

        return $log;
    }
}

// ============================================================
// EJECUCION
// ============================================================

$positional = array_values(array_filter(
    array_slice($argv, 1),
    fn(string $arg) => !str_starts_with($arg, '--')
));

$force = in_array('--force', $argv, true);
$list = in_array('--list', $argv, true);

$table = $positional[0] ?? null;
$entityArg = $positional[1] ?? null;

// Sin nombre de tabla: genera el CRUD de TODAS las tablas de la base.
$all = !$table && !$list;

try {
    $inspector = new TableInspector(Database::connection());

    if ($list) {
        echo "Tablas disponibles:\n\n";

        foreach ($inspector->tables() as $name) {
            echo "  - $name\n";
        }

        echo "\nUso:\n";
        echo "  php 2_generar_crud.php                               (CRUD de TODAS las tablas)\n";
        echo "  php 2_generar_crud.php <tabla> [Entidad] [--force]   (CRUD de una tabla)\n";
        echo "  php 2_generar_crud.php --list                        (lista las tablas)\n";
        exit(0);
    }

    $tables = $all ? $inspector->tables() : [$table];

    if (!$tables) {
        echo "No hay tablas. Corre primero: php bin/migrate.php\n";
        exit(0);
    }

    $generated = 0;
    $skipped = 0;

    foreach ($tables as $currentTable) {
        try {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $currentTable)) {
                throw new RuntimeException('Nombre de tabla invalido.');
            }

            $entity = (!$all && $entityArg !== null)
                ? $entityArg
                : Naming::studly(Naming::singular($currentTable));

            if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $entity)) {
                throw new RuntimeException(
                    'El nombre de la entidad debe ser StudlyCase. Ejemplo: Product.'
                );
            }

            $blueprint = new Blueprint(
                $currentTable,
                $entity,
                $inspector->columns($currentTable)
            );

            if (!$blueprint->writable()) {
                throw new RuntimeException(
                    'La tabla no tiene columnas editables.'
                );
            }

            $generator = new CrudGenerator(
                [
                    new EntityBuilder($blueprint),
                    new RepositoryInterfaceBuilder($blueprint),
                    new RepositoryBuilder($blueprint),
                    new ServiceBuilder($blueprint),
                    new ControllerBuilder($blueprint),
                    new IndexViewBuilder($blueprint),
                    new FormViewBuilder($blueprint),
                ],
                new FileWriter(__DIR__, $force)
            );

            echo "Generando CRUD de '$currentTable' (entidad $entity)\n\n";
            echo implode("\n", $generator->run()) . "\n";
            echo (new MenuLinker(__DIR__ . '/src/Views/layout.php'))
                ->add($currentTable, Naming::label($currentTable));
            echo "\n\nListo: /$currentTable\n\n";

            $generated++;
        } catch (RuntimeException $e) {
            // En modo --all una tabla problematica no frena las demas.
            if (!$all) {
                throw $e;
            }

            echo "SKIP '$currentTable': " . $e->getMessage() . "\n\n";
            $skipped++;
        }
    }

    if ($all) {
        echo "Resumen: $generated generadas, $skipped omitidas.\n";
    }

} catch (PDOException $e) {
    echo "ERROR: No se pudo conectar o consultar PostgreSQL.\n";
    echo "Detalle: " . $e->getMessage() . "\n";
    exit(1);

} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}