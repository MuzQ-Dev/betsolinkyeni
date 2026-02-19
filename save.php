<?php
/**
 * save.php — Minimal file I/O bridge (no framework, no DB)
 * Allowed files: data.json, btag.json, vis.json
 */
header('Content-Type: application/json');

$ALLOWED = ['data.json', 'btag.json', 'vis.json'];
$f = isset($_GET['f']) ? basename($_GET['f']) : '';

if (!in_array($f, $ALLOWED)) {
    http_response_code(403);
    echo '{"error":"forbidden"}';
    exit;
}

$DEFAULTS = [
    'data.json' => ['siteNumber' => '481', 'defaultRef' => 'MuzQ'],
    'btag.json' => [],
    'vis.json' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');

    if (isset($_GET['append'])) {
        // Append single item to array, with exclusive file lock to prevent race conditions
        $fp = fopen($f, file_exists($f) ? 'c+' : 'w+');
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $existing = json_decode($raw, true);
        if (!is_array($existing))
            $existing = [];
        $item = json_decode($input, true);
        if ($item !== null) {
            $existing[] = $item;
            // Keep max 15000 visit entries
            if (count($existing) > 15000)
                $existing = array_slice($existing, -15000);
        }
        $out = json_encode($existing);
        rewind($fp);
        fwrite($fp, $out);
        ftruncate($fp, strlen($out));
        flock($fp, LOCK_UN);
        fclose($fp);
    } else {
        // Full overwrite
        file_put_contents($f, $input, LOCK_EX);
    }
    echo '{"ok":true}';

} else {
    // Read
    if (file_exists($f)) {
        echo file_get_contents($f);
    } else {
        echo json_encode($DEFAULTS[$f]);
    }
}
