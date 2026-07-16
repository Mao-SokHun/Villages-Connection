<?php
function test() {
    global $pdo;
    $pdo = "connected";
    require 'test_inner.php';
}

file_put_contents('test_inner.php', '<?php echo "Value of pdo inside: " . $pdo; ?>');
test();
