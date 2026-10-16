<?php
/**
 * One-shot installer. Runs schema.sql + seeds.sql and creates the first admin user.
 *
 *   Visit https://your-site/api/bpr/install.php in a browser.
 *   Once installation succeeds, RENAME this file (or delete it) — it self-locks
 *   by creating install.php.done, but manual removal is safer.
 */
require __DIR__ . '/bootstrap.php';
use BPR\{DB, Auth, Utils};

$lock = __DIR__ . '/install.php.done';
if (file_exists($lock) && empty($_GET['force'])) {
    http_response_code(423);
    echo "Installer locked. Delete install.php.done to re-run.";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

function runSql(string $file): void {
    $sql = file_get_contents(__DIR__ . '/' . $file);
    if (!$sql) throw new RuntimeException("Cannot read $file");
    // Split on semicolons at end of line (naive — schema uses conventional statements).
    $stmts = preg_split('/;\s*[\r\n]+/', $sql);
    foreach ($stmts as $s) {
        $s = trim($s);
        if ($s === '' || $s[0] === '-') continue;
        DB::get()->exec($s);
    }
}

try {
    echo "Running schema.sql... ";
    runSql('schema.sql');
    echo "OK\n";

    echo "Running seeds.sql... ";
    runSql('seeds.sql');
    echo "OK\n";

    // Create admin user if none exists
    $count = (int) DB::scalar('SELECT COUNT(*) FROM bpr_users');
    if ($count === 0) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "\nNo admin user yet. Submit an admin email + password via POST:\n";
            echo "  curl -X POST -d 'email=you@x.com&password=YourStrongPw!' https://.../api/bpr/install.php\n";
            echo "or open this URL in the browser and fill the form below.\n";
            ?>
            <hr>
            <form method="post">
              <p>Admin email: <input name="email" type="email" required></p>
              <p>Password (min 12 chars): <input name="password" type="password" minlength="12" required></p>
              <button type="submit">Create admin</button>
            </form>
            <?php
            exit;
        }
        $email = Utils::email($_POST['email'] ?? '');
        $pw    = (string)($_POST['password'] ?? '');
        if (!$email || strlen($pw) < 12) { echo "Bad email or weak password"; exit; }
        DB::exec('INSERT INTO bpr_users (email, name, password_hash, role) VALUES (?,?,?, "admin")',
            [$email, 'Admin', Auth::hash($pw)]);
        echo "Admin user created: $email\n";
    } else {
        echo "Admin user(s) already exist ($count). Skipping.\n";
    }

    @file_put_contents($lock, "installed " . gmdate('c') . "\n");
    echo "\n✓ Installation complete.\n";
    echo "→ Admin panel: /admin/\n";
    echo "→ DELETE this file (install.php) for security.\n";

} catch (\Throwable $e) {
    http_response_code(500);
    echo "\n✗ Install failed: " . $e->getMessage() . "\n";
}
