<?php

use Shsk\Autoloader;

require_once 'src/Shsk/Autoloader.php';
new Autoloader();

use Shsk\Http\Request\PutRequestParser as Parser;
use function Shsk\Http\Request\is_uploaded_file as is_uploaded_file;
use function Shsk\Http\Request\move_uploaded_file as move_uploaded_file;

if ($_SERVER['REQUEST_METHOD'] === 'GET')  {
    $response = ['uuid' => uuid()];

    echo json_encode($response);

}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $parser = new Parser();
    $data = $parser->parse('php://input', true);

    if (!isset($_POST['uuid'])) {
        echo json_encode(['success' => false]);
        exit;
    }

    if (isset($_FILES['data']) && is_uploaded_file($_FILES['data']['tmp_name'])) {
        if (!isset($_POST['index'])) {
            echo json_encode(['success' => false]);
            exit;
        }

        // ファイルがアップロードされていた場合
        $file_name = sprintf("%s---%08d", $_POST['uuid'], $_POST['index']);
        move_uploaded_file($_FILES['data']['tmp_name'], $file_name);

        echo json_encode(['success' => true]);
        exit;
    } else if(isset($_POST['name'])) {
        // 全てのchunkがアップロードされた場合に通知されるファイル名
        $files = get_chunk_files($_POST['uuid']);
        $org_file_name = $join_file_name = $_POST['name'];

        // ファイル名が重複してた場合にファイル名の最後に数字をつける処理
        $i = 2;
        while (file_exists($join_file_name)) {
            $name = pathinfo($org_file_name, PATHINFO_FILENAME);
            $ext = pathinfo($org_file_name, PATHINFO_EXTENSION);
            $join_file_name = "{$name}({$i}).{$ext}";
            $i++;
        }
        $fp = fopen($join_file_name, 'wb');
        foreach ($files as $file) {
            $r = fopen($file, 'rb');
            while ($line = fgets($r, 4096)) {
                fwrite($fp, $line);
            }
            fclose($r);
        }
        fclose($fp);
        unlink_files($files);
    }
}

function unlink_files($files) {
    foreach ($files as $file) {
        unlink($file);
    }
}

function get_chunk_files($uuid)
{
    $files = glob("{$uuid}---*");
    sort($files);

    return $files;
}

function uuid()
{
    return uniqid('', true);
}

function debug_replace_linefeed($line)
{
    $line = str_replace("\r", "[CR]", $line);
    $line = str_replace("\n", "[LF]", $line);

    return $line;
}