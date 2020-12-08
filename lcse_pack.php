<?php
$_INT_KEY = 0x02020202;
$_SNX_KEY = 0x03;

$_FILENAME_KEY = 0x02;
$_FILENAME_SIZE = 0x40;

$_LCSP_TYPE = array(
    'snx' => 1,
    'bmp' => 2,
    'png' => 3,
    'wav' => 4,
    'ogg' => 5
);

ini_set('memory_limit', '4G');

if (!isset($argv[2])) {
    die("Usage: lcse_pack.php <u[npack]|p[ack]> <Dir|File> [Output]\n\nExample:\nlcse_pack.php u lcsebody1 out\nlcse_pack.php p out lcsebody1");
}

function snx_xor($data)
{
    global $_SNX_KEY;
    for ($i = 0; $i < strlen($data); $i++) {
        $data[$i] = chr(ord($data[$i]) ^ $_SNX_KEY);
    }
    return $data;
}

if ($argv[1][0] == 'u') {
    // Unpacking

    function readInt($xor = true)
    {
        global $lst, $_INT_KEY;
        $xor = $xor ? $_INT_KEY : 0;
        return unpack('V', fread($lst, 4))[1] ^ $xor;
    }

    $lst = fopen($argv[2] . '.lst', 'r');
    $pak = fopen($argv[2], 'r');
    if (!$pak || !$lst) {
        die('Unable to open file');
    }

    $target = $argv[3] ?? ($argv[2] . '_extracted');
    @mkdir($target, 0777, true);

    $count =  readInt();
    if (filesize($argv[2] . '.lst') != $count * ($_FILENAME_SIZE + 12) + 4) {
        die('Bad lst file size');
    }

    echo ('[INFO] Total Files: ' . $count . PHP_EOL);

    for ($i = 0; $i < $count; $i++) {
        $offset = readInt();
        $size = readInt();

        $name = '';
        foreach (str_split(rtrim(fread($lst, $_FILENAME_SIZE), chr(0))) as $c) {
            $name .= chr(ord($c) ^ $_FILENAME_KEY);
        }

        $ext = readInt(false);
        if (($k = array_search($ext, $_LCSP_TYPE)) !== false) {
            $ext = $k;
        }
        $name .= '.' . $ext;

        echo ('[INFO] Processing: ' . $name  . ', offset=' . $offset . ', size=' . $size . PHP_EOL);

        fseek($pak, $offset);
        $data = fread($pak, $size);
        file_put_contents($target . '/' . $name, $ext == 'snx' ? snx_xor($data) : $data);
    }
} else if ($argv[1][0] == 'p') {
    // Packing

    function listDir($dir, $ext = false)
    {
        $ret = array();
        if (is_dir($dir) && $dp = @opendir($dir)) {
            while (($file = @readdir($dp)) !== false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $file = $dir . '/' . $file;
                if (is_dir($file)) {
                    $ret = array_merge($ret, listDir($file, $ext));
                } else if ($ext === false || strtolower(substr($file, strlen($file) - strlen($ext) - 1)) == strtolower('.' . $ext)) {
                    $ret[] = $file;
                }
            }
            @closedir($dp);
        }
        return $ret;
    }

    function writeInt($data, $xor = true)
    {
        global $lst, $_INT_KEY;
        if (fwrite($lst, pack('V', $xor ? $data ^ $_INT_KEY : $data)) === false) {
            die('Something happened');
        }
    }

    $target = $argv[3] ?? ($argv[2] . '_pak');

    $pak = fopen($target, 'w+');
    $lst = fopen($target  . '.lst', 'w+');
    if (!$pak || !$lst) {
        die('Unable to create file');
    }

    $files = listDir($argv[2]);

    writeInt(count($files));

    foreach ($files as $file) {
        echo ('[INFO] Processing: ' . $file . PHP_EOL);

        $ext = pathinfo($file, PATHINFO_EXTENSION);

        writeInt(ftell($pak)); // File Offset
        writeInt(filesize($file)); // File Length

        // File Name
        $name = '';
        foreach (str_split(mb_convert_encoding(basename($file, '.' . $ext), 'SHIFT-JIS')) as $c) {
            $name .= chr(ord($c) ^ $_FILENAME_KEY);
        }
        if (strlen($name) > $_FILENAME_SIZE) {
            echo ('[WARN] File name too long: ' . $file . PHP_EOL);
            $name = substr($name, 0, $_FILENAME_KEY);
        }
        $name = str_pad($name, $_FILENAME_SIZE, chr(0x00));
        fwrite($lst, $name);

        // File Type
        $ext = strtolower($ext);
        if (isset($_LCSP_TYPE[$ext])) {
            writeInt($_LCSP_TYPE[$ext], false);
        } else if (!is_numeric($ext)) {
            die('Bad extension, remove all file not to be packed and try again');
        } else {
            writeInt(intval($ext), false);
        }

        // Payload
        $data = file_get_contents($file);
        fwrite($pak, $ext == 'snx' ? snx_xor($data) : $data);
    }
} else {
    die('Bad action');
}

echo ('[INFO] All done!');
