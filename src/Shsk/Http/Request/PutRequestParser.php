<?php

namespace Shsk\Http\Request;

class PutRequestParser
{
    const LF = "\r\n";
    
    private static $shutdownFunctionRegistered = false;
    private static $temporaryFiles = [];

    public function __construct()
    {
        if (self::$shutdownFunctionRegistered === false) {
            register_shutdown_function([self::class, 'destroyTemporaryFiles']);
            self::$shutdownFunctionRegistered = true;
        }
    }

    public function parse($filePath = 'php://input', $updateGlobal = false)
    {
        $put = fopen($filePath, 'rb');

        // get boundary
        $boundary = fgets($put, 4096);
        $ending_boundary = str_replace(self::LF, "--" . self::LF, $boundary);

        // split unit by boundary
        $sections = [];
        $block = '';
        while ($line = fgets($put, 4096)) {
            if ($line === $boundary) {
                $sections[] = $block;
                $block = '';
                continue;
            }

            if ($line === $ending_boundary) {
                $sections[] = $block;
                $block = '';
                break;
            }

            $block .= $line;
        }

        $results = [];
        foreach ($sections as $section) {
            $results[] = $this->parseSection($section);
        }

        if ($updateGlobal === true) {
            $this->updateGlobalVariables($results);
        }

        return $results;
    }

    private function parseSection($section)
    {
        $return = [
            'headers' => [],
            'name' => '',
            'filename' => '',
            'body' => '',
        ];

        list ($headerString, $contentString) = explode(self::LF . self::LF, $section, 2);

        // parse headers
        $headerSection = [];
        $headers = explode(self::LF, $headerString);
        foreach ($headers as $headerLine) {
            preg_match('/^([^:]+):(.+)/', $headerLine, $matches);
            $type = strtolower($matches[1]);
            $value = $matches[2];
            $headerSection[$type] = $value;
        }
        $return['headers'] = $headerSection;

        if (isset($headerSection['content-disposition'])) {
            $values = $this->explodeHeaderValue($headerSection['content-disposition']);
            $return['name'] = $values['options']['name'] ?? '';
            $return['filename'] = $values['options']['filename'] ?? '';
        }

        // strip linefeed
        $return['body'] = substr($contentString, 0, -2);

        return $return;
    }

    private function explodeHeaderValue($rawValue)
    {
        $return = [
            'values' => [],
            'options' => [],
        ];
        $values = explode(';', $rawValue);
        foreach ($values as $value) {
            if (preg_match('/([^\s]+?)\s*=\s*"([^"]+)"/', $value, $matches)) {
                $return['options'][strtolower($matches[1])] = $matches[2];
            } else {
                $return['values'][] = $value;
            }
        }

        return $return;
    }

    private function updateGlobalVariables($sections)
    {
        foreach ($sections as $section) {
            $name = $section['name'];
            if (empty($name)) {
                continue;
            }
            if (isset($section['headers']['content-type']) && !empty($section['filename'])) {
                // insert $_FILES

                // create temporary file
                $tmpDir = ini_get('upload_tmp_dir');
                if (empty($tmpDir)) {
                    $tmpDir = sys_get_temp_dir();
                }
                $tmp_name = tempnam($tmpDir, 'php');
                file_put_contents($tmp_name, $section['body']);
                self::$temporaryFiles[] = $tmp_name;

                $_FILES = $this->insertFileArray($name, [
                    'name' => $section['filename'],
                    'type' => $section['headers']['content-type'],
                    'tmp_name' => $tmp_name,
                    'error' => 0,
                    'size' => strlen($section['body']),
                ], $_FILES);
            } else {
                // insert $_POST (super global $_PUT doesn't exist, so use it instead)
                $_POST = $this->insertArray($name, $section['body'], $_POST);
            }
        }
    }

    private function insertFileArray($keyString, $values, $result = [])
    {
        $keys = $this->explodeArrayKeyString($keyString);
        $key = array_shift($keys);
        if (count($keys) === 0) {
            $result[$key] = $values;
            return $result;
        }
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $fileKey) {
            $value = $values[$fileKey];
            $insertKeyString = "{$key}[{$fileKey}][" . implode('][', $keys) . "]";
            $result = $this->insertArray($insertKeyString, $value, $result);
        }
        return $result;
    }

    private function explodeArrayKeyString($keyString)
    {
        $start = strpos($keyString, '[');
        if ($start === false) {
            return [$keyString];
        }
        $keys = [];
        $end = strpos($keyString, ']');
        $keys[] = substr($keyString, 0, $start);
        $children = explode(']', substr($keyString, $start));
        array_pop($children);
        foreach ($children as $childKey) {
            $keys[] = ltrim($childKey, '[');
        }

        return $keys;
    }

    private function shiftArrayKeyString(&$keyString)
    {
        if ($keyString === '') {
            return null;
        }
        $start = strpos($keyString, '[');
        $end = strpos($keyString, ']');
        if ($start === false) {
            $currentName = $keyString;
            $keyString = '';
            return $currentName;
        }
        $currentName = substr($keyString, 0, $start);
        $keyString = substr($keyString, $start);
        $keyString = preg_replace('/^\[([^\]]+)\]/', '$1', $keyString);

        return $currentName;
    }

    private function insertArray($keyString, $values, $result = [])
    {
        $current = &$result;

        $keys = $this->explodeArrayKeyString($keyString);

        while (true) {
            $currentName = array_shift($keys);
            if (count($keys) === 0) {
                break;
            }

            if (!array_key_exists($currentName, $current)) {
                $current[$currentName] = [];
            }
            $current = &$current[$currentName];
            if (!is_array($current)) {
                $current = [];
            }
        }

        $current[$currentName] = $values;

        return $result;
    }

    public static function destroyTemporaryFiles()
    {
        foreach (self::$temporaryFiles as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    public static function isUploadedFile($tmpFile): bool
    {
        if (in_array($tmpFile, self::$temporaryFiles)) {
            return true;
        }
        return \is_uploaded_file($tmpFile);
    }

    public static function moveUploadedFile($from, $to): bool
    {
        if (self::isUploadedFile($from)) {
            return rename($from, $to);
        }
        return \move_uploaded_file($from, $to);
    }
}

function is_uploaded_file($tmp_file): bool
{
    return PutRequestParser::isUploadedFile($tmp_file);
}

function move_uploaded_file($from, $to): bool
{
    return PutRequestParser::moveUploadedFile($from, $to);
}

