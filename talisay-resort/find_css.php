<?php
$file = 'C:\\Users\\Louie Jay\\.gemini\\antigravity-ide\\brain\\893638fe-b7a7-44e3-b2dc-97dc4cbc4f9d\\.system_generated\\logs\\transcript_full.jsonl';
$handle = fopen($file, 'r');
$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (strpos($line, '.auth-hero-title {') !== false && strpos($line, 'login.blade.php') !== false) {
        $json = json_decode($line, true);
        echo "Found at log line $lineNum:\n";
        echo substr($json['content'] ?? '', 0, 1500) . "\n---\n";
    }
}
fclose($handle);
