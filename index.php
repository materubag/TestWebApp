<?php
declare(strict_types=1);

const STORAGE = __DIR__ . '/storage.json';

function loadTasks(): array {
    if (!file_exists(STORAGE)) {
        file_put_contents(STORAGE, json_encode([]));
    }
    $raw = file_get_contents(STORAGE);
    return $raw ? json_decode($raw, true) ?? [] : [];
}

function saveTasks(array $tasks): void {
    file_put_contents(STORAGE, json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$tasks = loadTasks();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $text = trim($_POST['text'] ?? '');
        if ($text === '') {
            $errors[] = "La tarea no puede estar vacía.";
        } else {
            $tasks[] = [
                'id' => uniqid('t_', true),
                'text' => $text,
                'done' => false,
                'created_at' => date('c')
            ];
            saveTasks($tasks);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } elseif ($action === 'toggle') {
        $id = $_POST['id'] ?? '';
        foreach ($tasks as &$t) {
            if ($t['id'] === $id) {
                $t['done'] = !$t['done'];
                break;
            }
        }
        unset($t);
        saveTasks($tasks);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $tasks = array_values(array_filter($tasks, fn($t) => $t['id'] !== $id));
        saveTasks($tasks);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mini TODO PHP</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body { font-family: system-ui, Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; background:#f7f7f7;}
        h1 { margin-top:0; }
        form.inline { display:inline; }
        .task { background:#fff; padding:.75rem 1rem; margin:.5rem 0; border-radius:6px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 1px 3px rgba(0,0,0,.08);}
        .done span.text { text-decoration: line-through; color:#666;}
        .badge { font-size:.65rem; padding: .2rem .4rem; background:#eee; border-radius:4px; margin-left:.5rem;}
        .errors { background:#ffe8e8; color:#900; padding:.5rem .75rem; border-radius:6px; margin-bottom:1rem;}
        button { cursor:pointer; }
    </style>
</head>
<body>
    <h1>Mini TODO PHP (Azure Ready)</h1>
    <p>Ejemplo simple de almacenamiento en archivo JSON. No usar en producción tal cual.</p>

    <?php if ($errors): ?>
        <div class="errors">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="action" value="add">
        <input name="text" placeholder="Nueva tarea..." style="width:60%;padding:.5rem;" required>
        <button type="submit">Añadir</button>
    </form>

    <h2>Listado</h2>
    <?php if (!$tasks): ?>
        <p>No hay tareas todavía.</p>
    <?php else: ?>
        <?php foreach ($tasks as $t): ?>
            <div class="task <?= $t['done'] ? 'done':'' ?>">
                <div>
                    <span class="text"><?= htmlspecialchars($t['text'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="badge"><?= $t['done'] ? 'Completada' : 'Pendiente' ?></span>
                </div>
                <div>
                    <form method="post" class="inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit"><?= $t['done'] ? 'Reabrir' : 'Completar' ?></button>
                    </form>
                    <form method="post" class="inline" onsubmit="return confirm('¿Eliminar tarea?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <footer style="margin-top:2rem;font-size:.8rem;color:#555;">
        Ejemplo educativo. Considera usar una base de datos en producción (MySQL, PostgreSQL, Azure Database).
    </footer>
</body>
</html>
