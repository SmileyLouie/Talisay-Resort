<?php
$file = 'C:\\Users\\Louie Jay\\.gemini\\antigravity-ide\\brain\\893638fe-b7a7-44e3-b2dc-97dc4cbc4f9d\\.system_generated\\logs\\transcript_full.jsonl';
$handle = fopen($file, 'r');
$lineNum = 0;
$targetLines = [9608, 9610, 9612, 9614];
$fullContent = '';

while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (in_array($lineNum, $targetLines)) {
        $json = json_decode($line, true);
        $raw = $json['content'] ?? '';
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

file_put_contents('login_3rd_image.blade.php', $fullContent);
echo "login_3rd_image.blade.php length: " . strlen($fullContent) . " bytes\n";
echo "Total lines: " . substr_count($fullContent, "\n") . "\n";
