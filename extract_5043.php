<?php
$file = 'C:\\Users\\Louie Jay\\.gemini\\antigravity-ide\\brain\\893638fe-b7a7-44e3-b2dc-97dc4cbc4f9d\\.system_generated\\logs\\transcript_full.jsonl';
$handle = fopen($file, 'r');
$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if ($lineNum === 5043) {
        $json = json_decode($line, true);
        $raw = $json['content'] ?? '';
        $lines = explode("\n", $raw);
        $fullContent = '';
        $collecting = false;
        foreach ($lines as $l) {
            if (preg_match('/^Showing lines 1 to /', $l)) {
                $collecting = true;
                continue;
            }
            if ($collecting) {
                if (preg_match('/^The following code has been modified/', $l)) {
                    continue;
                }
                if (preg_match('/^The above content does NOT show/', $l)) {
                    break;
                }
                if (preg_match('/^\d+:\s?(.*)$/', $l, $matches)) {
                    $fullContent .= $matches[1] . "\n";
                }
            }
        }
        file_put_contents('exact_image3_login.blade.php', $fullContent);
        echo "Extracted log line 5043! Length: " . strlen($fullContent) . " bytes\n";
        echo "Total lines: " . substr_count($fullContent, "\n") . "\n";
        break;
    }
}
fclose($handle);
