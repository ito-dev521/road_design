<?php
echo "Testing auth.php...<br>";

try {
    require_once 'config.php';
    echo "✓ config.php loaded<br>";

    require_once 'auth.php';
    echo "✓ auth.php loaded<br>";

    $auth = new Auth();
    echo "✓ Auth class instantiated<br>";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>
