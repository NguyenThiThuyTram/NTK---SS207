<?php
echo "<h3>System Diagnostics</h3>";
echo "PHP User: " . exec('whoami') . "<br>";
$dir = __DIR__ . '/assets/uploads/reviews';
echo "Target Dir: $dir <br>";
echo "Exists? " . (is_dir($dir) ? "YES" : "NO") . "<br>";
if (is_dir($dir)) {
    echo "Permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "<br>";
    echo "Is Writable? " . (is_writable($dir) ? "YES" : "NO") . "<br>";
}
$test_file = $dir . '/test.txt';
$res = @file_put_contents($test_file, 'test');
if ($res !== false) {
    echo "Test Write: SUCCESS<br>";
    @unlink($test_file);
} else {
    $err = error_get_last();
    echo "Test Write: FAILED - " . ($err['message'] ?? 'Unknown error') . "<br>";
}
