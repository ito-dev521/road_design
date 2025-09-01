<?php
echo "Testing database.php...<br>";

try {
    require_once 'config.php';
    echo "✓ config.php loaded<br>";

    require_once 'database.php';
    echo "✓ database.php loaded<br>";

    $db = new Database();
    echo "✓ Database class instantiated<br>";

    echo "<h3>Database Configuration</h3>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Port: " . DB_PORT . "</li>";
    echo "<li>Database: " . DB_NAME . "</li>";
    echo "<li>User: " . DB_USER . "</li>";
    echo "<li>Charset: " . DB_CHARSET . "</li>";
    echo "</ul>";

    echo "<h3>Testing Connection...</h3>";
    $connection = $db->getConnection();

    if ($connection) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";

        // MariaDBバージョン確認
        try {
            $stmt = $connection->query("SELECT VERSION() as version");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>MariaDB Version: " . $result['version'] . "</p>";
        } catch (Exception $e) {
            echo "<p>MariaDB version check failed: " . $e->getMessage() . "</p>";
        }

        // 利用可能なデータベース一覧
        try {
            $stmt = $connection->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<h4>Available Databases:</h4>";
            echo "<ul>";
            foreach ($databases as $database) {
                echo "<li>" . htmlspecialchars($database) . "</li>";
            }
            echo "</ul>";
        } catch (Exception $e) {
            echo "<p>Database list failed: " . $e->getMessage() . "</p>";
        }

    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
        echo "<p><strong>Possible causes:</strong></p>";
        echo "<ul>";
        echo "<li>Incorrect host: " . DB_HOST . "</li>";
        echo "<li>Wrong username or password</li>";
        echo "<li>Database does not exist</li>";
        echo "<li>MySQL service is down</li>";
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>
