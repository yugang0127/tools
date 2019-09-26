<?php
/**
 * 对aTimeLogger中记录的时间日志反转按照正常的时间顺序排列
 */
$path = "/users/yugang/Downloads/report.html";
$handle = fopen($path, "r");
$content = fread($handle, filesize($path));

preg_match_all("/<tr>\s*?(<td>.*?<\/td>\s*?){5}\s*?<\/tr>/",  $content, $matches);
$reverse = array_reverse($matches[0]);
//var_dump($matches);
//var_dump($reverse);
$reverseStr = implode("\n\n", $reverse);
$reverseStr = "</thead>\n\r<tbody>" . $reverseStr . "\r\n</tbody>";
$replace = preg_replace("/<\/thead>\s*?<tbody>[\s\S]+?<\/tbody>/", $reverseStr, $content);
file_put_contents("/users/yugang/Downloads/report_reverse.html", $replace);
