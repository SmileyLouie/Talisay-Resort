<?php
$file = 'C:\\Users\\Louie Jay\\.gemini\\antigravity-ide\\brain\\893638fe-b7a7-44e3-b2dc-97dc4cbc4f9d\\.system_generated\\logs\\transcript.jsonl';
$handle = fopen($file, 'r');
$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (strpos($line, 'login.blade.php') !== false) {
        $json = json_decode($line, true);
        $type = $json['type'] ?? '';
        $tool = $json['tool_calls'][0]['name'] ?? '';
        echo "Line $lineNum | Type: $type | Tool: $tool\n";
    }
}
fclose($handle);
