<?php

if ($_SERVER['REQUEST_METHOD'] === 'GET')  {
    $response = ['uuid' => uuid()];

    echo json_encode($response);

}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = parse_put_contents('php://input');
    if (!isset($data['name'])) {
        $file_name = sprintf("%s---%08d", $data['uuid'], $data['index']);
        file_put_contents($file_name, $data['blob']);
        echo json_encode(['success' => true]);
    } else {
        $files = get_chunk_files($data['uuid']);
        $org_file_name = $join_file_name = $data['name'];
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

function l($value) {
    file_put_contents('log.txt', $value . PHP_EOL, FILE_APPEND);
}

function parse_put_contents($data)
{
    $results = [];
    $fp = fopen($data, 'rb');
    // 1行目はBoundary文字列
    $boundary = fgets($fp, 4096);
    $header_section = true;
    $name = '';
    $value = '';
    $blob = '';
    $blob_mode = false;
    while ($line = fgets($fp, 4096)) {
        if ($header_section) {
            // header section
            if ($line === "\r\n") {
                $header_section = false;
                continue;
            }
            if (strpos($line, 'Content-Disposition') === 0) {
                if (preg_match('/name="([^"]+)"/i', $line, $match)) {
                    $name = $match[1];
                    if (stripos($line, 'filename="blob"') !== false) {
                        $blob_mode = true;
                    }
                }
            } else if (strpos($line, 'Content-Type') === 0) {

            }
            continue;
        }

        $valid_section = $line === $boundary;
        $valid_input_end = $line === rtrim($boundary) . "--\r\n";

        if ($blob_mode === false && $valid_section === false && $valid_input_end === false) {
            // text section
            $value .= $line;
        }

        if ($blob_mode === true && $valid_section === false && $valid_input_end === false) {
            $blob .= $line;
        }

        if ($valid_section === true || $valid_input_end === true) {
            if ($blob_mode === false) {
                $results[$name] = rtrim($value);
            } else if ($blob_mode === true) {
                $results['blob'] = substr($blob, 0, -2);
            }

            $name = '';
            $value = '';
            $blob = '';
            $blob_mode = false;
            $header_section = true;
            if ($valid_input_end === true) {
                break;
            }
        }
    }
    fclose($fp);
    return $results;
}

function get_name($line)
{
    $line = rtrim($line);
    if (strpos($line, 'Content-Disposition') === 0) {
        
    }
}

function debug_replace_linefeed($line)
{
    $line = str_replace("\r", "[CR]", $line);
    $line = str_replace("\n", "[LF]", $line);

    return $line;
}