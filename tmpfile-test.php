<?php

header('Content-Type: text/plain');

echo "PHP VERSION: " . PHP_VERSION . PHP_EOL;
echo "PHP SAPI: " . PHP_SAPI . PHP_EOL;
echo "TEMP: " . sys_get_temp_dir() . PHP_EOL;
echo "UPLOAD TEMP: " . ini_get('upload_tmp_dir') . PHP_EOL;
echo PHP_EOL;

$tmp = tmpfile();

if ($tmp === false) {
    echo "WEB TMPFILE FAILED" . PHP_EOL;
    exit;
}

echo "WEB TMPFILE OK" . PHP_EOL;

$meta = stream_get_meta_data($tmp);

echo "URI: " . $meta['uri'] . PHP_EOL;

fclose($tmp);