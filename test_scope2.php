<?php
function test() {
    require 'test_database.php';
    require 'test_inner.php';
}

file_put_contents('test_database.php', '<?php global $pdo; $pdo = "connected"; ?>');
file_put_contents('test_inner.php', '<?php echo "Value of pdo inside: " . (isset($pdo) ? $pdo : "NULL"); ?>');

test();
