<?php
// Created by tcatpwn
// Version 1: 6/25/2025

session_start();
mysqli_report(MYSQLI_REPORT_STRICT);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Handle login
if (!isset($_SESSION['db'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $conn = new mysqli($_POST['host'], $_POST['user'], $_POST['pass']);
            $conn->close();
            $_SESSION['db'] = [
                'host' => $_POST['host'],
                'user' => $_POST['user'],
                'pass' => $_POST['pass']
            ];
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } catch (mysqli_sql_exception $e) {
            $error = "Login failed.";
        }
    }
    ?>
    <!DOCTYPE html><html><head><title>Login</title></head><body>
    <h3>MySQL Login</h3>
    <?php if (!empty($error)) echo "<div style='color:red;'>$error</div>"; ?>
    <form method="post">
        <input name="host" placeholder="Host" required><br>
        <input name="user" placeholder="User" required><br>
        <input name="pass" placeholder="Pass" type="password"><br>
        <button>Connect</button>
    </form></body></html>
    <?php exit;
}

// Load creds and connect
extract($_SESSION['db']);
$currentDb = $_SESSION['selected_db'] ?? null;
$conn = new mysqli($host, $user, $pass, $currentDb ?: null);

// Export CSV
if (isset($_POST['export']) && is_array($_SESSION['last'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="result.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array_keys($_SESSION['last'][0]));
    foreach ($_SESSION['last'] as $r) fputcsv($out, $r);
    exit;
}

// Handle DB selection
if (isset($_POST['select_db'])) {
    $_SESSION['selected_db'] = $_POST['select_db'];
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Run query
$result = $error = null;
if (!empty($_POST['query'])) {
    try {
        $res = $conn->query($_POST['query']);
        if ($res instanceof mysqli_result) {
            $result = $res->fetch_all(MYSQLI_ASSOC);
            $_SESSION['last'] = $result;
        } else {
            $result = "Query OK. Rows: " . $conn->affected_rows;
            $_SESSION['last'] = null;
        }
    } catch (mysqli_sql_exception $e) {
        $error = "Query failed.";
        $_SESSION['last'] = null;
    }
}

// Get DB list
$dbs = [];
try {
    $r = $conn->query("SHOW DATABASES");
    while ($row = $r->fetch_row()) $dbs[] = $row[0];
} catch (mysqli_sql_exception $e) { }
?>
<!DOCTYPE html><html><head><title>SQL Client</title>
<style>
body { font-family:sans-serif; margin:20px; }
textarea { width:100%; height:100px; }
table { border-collapse:collapse; width:100%; margin-top:10px; }
td,th { border:1px solid #ccc; padding:6px; }
</style></head><body>
<h3>SQL Client</h3>
<a href="?logout=1" style="float:right;">Logout</a>
<form method="post">
<select name="select_db" onchange="this.form.submit()">
    <option value="">-- Select DB --</option>
    <?php foreach ($dbs as $db) echo "<option " . ($db === $currentDb ? "selected" : "") . ">$db</option>"; ?>
</select>
</form>

<form method="post">
<textarea name="query"><?= htmlspecialchars($_POST['query'] ?? '') ?></textarea><br>
<button type="submit">Run Query</button>
<?php if (!empty($_SESSION['last'])): ?>
    <button name="export" value="1">Export CSV</button>
<?php endif; ?>
</form>

<?php if ($error): ?>
<div style="color:red;"><strong><?= $error ?></strong></div>
<?php elseif (is_array($result)): ?>
<table><tr>
<?php foreach (array_keys($result[0]) as $col) echo "<th>" . htmlspecialchars($col) . "</th>"; ?>
</tr>
<?php foreach ($result as $row): echo "<tr>";
foreach ($row as $v) echo "<td>" . htmlspecialchars($v) . "</td>";
echo "</tr>"; endforeach; ?>
</table>
<?php elseif ($result): ?>
<div><strong><?= htmlspecialchars($result) ?></strong></div>
<?php endif; ?>
</body></html>
