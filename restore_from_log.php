<?php
$file = 'C:\\Users\\Louie Jay\\.gemini\\antigravity-ide\\brain\\893638fe-b7a7-44e3-b2dc-97dc4cbc4f9d\\.system_generated\\logs\\transcript_full.jsonl';
$handle = fopen($file, 'r');
$lineNum = 0;
$targetLines = [9698, 9700, 9702, 9704];
$fullContent = '';

while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (in_array($lineNum, $targetLines)) {
        $json = json_decode($line, true);
        $raw = $json['content'] ?? '';
        // Extract lines from "Showing lines X to Y" down to end
        // Strip line numbers like "1: ", "2: ", etc.
        $lines = explode("\n", $raw);
        $collecting = false;
        foreach ($lines as $l) {
            if (preg_match('/^Showing lines \d+ to \d+/', $l)) {
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
    }
}
fclose($handle);

file_put_contents('reconstructed_login.blade.php', $fullContent);
echo "Done! Reconstructed length: " . strlen($fullContent) . " bytes\n";
