php
<?php
function debugLog($message) {
    error_log($message);
    file_put_contents('debug.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}
?>