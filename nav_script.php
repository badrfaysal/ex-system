<?php
$nav = file_get_contents('nav_block.txt');
preg_match_all('/<div class="mx-2 mb-0\.5">.*?<\/div>\s*<\/div>/s', $nav, $matches);
foreach ($matches[0] as $i => $s) {
    if (preg_match('/<span>(.*?)<\/span>/', $s, $title)) {
        echo "$i: " . trim($title[1]) . "\n";
    }
}
