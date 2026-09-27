<?php

echo "PHP TEMP TEST" . PHP_EOL;
echo "sys_get_temp_dir = " . sys_get_temp_dir() . PHP_EOL;
echo "upload_tmp_dir   = " . ini_get("upload_tmp_dir") . PHP_EOL;

$file = tempnam(sys_get_temp_dir(), "rizky_");

if ($file === false) {
    echo "TEMP FILE FAILED" . PHP_EOL;
    exit(1);
}

echo "temporary file = " . $file . PHP_EOL;

file_put_contents($file, "TEST");

if (file_exists($file)) {
    echo "TEMP FILE OK" . PHP_EOL;
    unlink($file);
} else {
    echo "TEMP FILE FAILED" . PHP_EOL;
}