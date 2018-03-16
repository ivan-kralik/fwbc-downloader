<?php

require('config.php');
require('functions.php');

libxml_use_internal_errors(true);

$fp = fopen(__FILE__, 'r');

if(!flock($fp, LOCK_EX | LOCK_NB)) {
    echo "Process already running";
    exit(-1);
}

info('Downloading FWBC Sermons page');

$page = file_get_contents($url);

file_put_contents($dir . '/page.html', $page);

$doc = new \DOMDocument();
$doc->loadHTML(preg_replace('#</tr>\s*<td>#i', '<tr><td>', $page));

$xpath = new \DOMXPath($doc);

foreach ($xpath->query('//table[@class = "box-table-a"]/tbody/tr[count(td) = 4]') as $rowNode) {
    if (!isDownloadAllowed()) {
        break;
    }
    
    $columns = $xpath->query('td', $rowNode);
    $dateString = trim(preg_replace('/,.*/', '', $columns->item(0)->nodeValue));
    $name = trim($columns->item(1)->nodeValue);
    
    if (!($date = \DateTime::createFromFormat('m/d/y', $dateString) ?: \DateTime::createFromFormat('d/m/Y', '01/' . $dateString))) {
        error("Failed to download video '{$name}': invalid date");
        
        continue;
    }
    
    $slug = trim(preg_replace('/\W+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $name))), '-');
    $baseDir = $dir . '/' . $date->format('Y') . '/' . $date->format('m');
    $prefix = $date->format('Y-m-d') . '-';
    $mp3Nodes = $xpath->query('a[1]/@href', $columns->item(2));
    $mp3Url = $mp3Nodes->length == 0 ? null : trim($mp3Nodes->item(0)->nodeValue);
    $mp3Path = $baseDir . '/' . $prefix . $slug . '.mp3';
    $youtubeNodes = $xpath->query('a[contains(@href, "youtube")]/@href', $columns->item(2));
    $youtubeUrl = $youtubeNodes->length == 0 ? null : trim($youtubeNodes->item(0)->nodeValue);
    $youtubePath = $baseDir . '/' . $prefix . $slug . '.mp4';
    
    if (!file_exists($baseDir)) {
        mkdir($baseDir, 0777, true);
    }
    
    $youtubeStatus = file_exists($youtubePath);
    
    if (!$youtubeStatus && $youtubeUrl) {
        info('Downloading video: ' . $name);
        $youtubeStatus = downloadYoutube($youtubeUrl, $youtubePath);
    }
    
    if (!$youtubeStatus && !file_exists($mp3Path) && $mp3Url) {
        info('Downloading audio: ' . $name);
        downloadFile($mp3Url, $mp3Path);
    }
}

fclose($fp);
