<?php

echo '<pre>';

echo "PHP: " . PHP_VERSION . PHP_EOL;
echo "SAPI: " . PHP_SAPI . PHP_EOL;
echo "TMP: " . sys_get_temp_dir() . PHP_EOL;
echo "UPLOAD TMP: " . var_export(ini_get('upload_tmp_dir'), true) . PHP_EOL;

echo PHP_EOL . "FILES:" . PHP_EOL;
var_dump($_FILES);

if (!empty($_FILES['file']['tmp_name'])) {
    echo PHP_EOL . "TMP EXISTS: ";
    var_dump(file_exists($_FILES['file']['tmp_name']));

    echo "TMP READABLE: ";
    var_dump(is_readable($_FILES['file']['tmp_name']));

    echo "TMP PATH: ";
    var_dump($_FILES['file']['tmp_name']);
}

echo '</pre>';
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file">
    <button type="submit">Upload</button>
</form>