<?php
echo "Testing api.php...<br>";

try {
    require_once 'config.php';
    echo "✓ config.php loaded<br>";

    require_once 'auth.php';
    echo "✓ auth.php loaded<br>";

    require_once 'database.php';
    echo "✓ database.php loaded<br>";

    require_once 'api.php';
    echo "✓ api.php loaded<br>";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>
