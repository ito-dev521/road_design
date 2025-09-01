<?php
echo "Testing config.php...<br>";

try {
    require_once 'config.php';
    echo "✓ config.php loaded successfully<br>";
    echo "DB_HOST: " . DB_HOST . "<br>";
    echo "DB_NAME: " . DB_NAME . "<br>";
    echo "Timezone: " . date_default_timezone_get() . "<br>";
} catch (Exception $e) {
    echo "✗ Error in config.php: " . $e->getMessage() . "<br>";
}

echo "PHP Version: " . phpversion() . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
?>
