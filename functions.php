<?php

function error($msg) {
    error_log(date('[Y-m-d H:i:s] ') . 'ERROR: ' . $msg);
}

function info($msg) {
    error_log(date('[Y-m-d H:i:s] ') . 'INFO: ' . $msg);
}

function debug($msg) {
    error_log($msg);
}

function getFilesizeSumForDay($folder, \DateTime $day) {
    $start = clone $day;
    $start->setTime(0, 0, 0);
    $end = clone $start;
    $end->add(new \DateInterval('P1D'));
    
    $files = array_values(array_filter(scandir($folder), function ($name) { return !in_array($name, array('.', '..')); }));
    $sum = 0;
    
    foreach ($files as $file) {
        $path = $folder . '/' . $file;
        
        if (is_dir($path)) {
            $sum += getFilesizeSumForDay($path, $day);
        } else if (is_file($path)) {
            $mtime = new \DateTime(date('Y-m-d H:i:s', filemtime($path)));
            
            if ($mtime >= $start && $mtime < $end) {
                $sum += filesize($path);
            }
        }
    }
    
    return $sum;
}

function isTimeAllowed(\DateTime $time) {
    global $allowFrom, $allowTo;
    
    $timeString = $time->format('H:i');
    
    return $timeString >= $allowFrom && $timeString <= $allowTo;
}

function isDownloadAllowed() {
    global $dailyLimit, $dir;
    
    $reason = '';
    
    if (!($timeAllowed = isTimeAllowed(new \DateTime()))) {
        $reason .= ' time';
    }
    
    if (!($sizeAllowed = (getFilesizeSumForDay($dir, new \DateTime()) < $dailyLimit))) {
        $reason .= ' size';
    }
    
    if (!$timeAllowed || !$sizeAllowed) {
        info('Download not allowed: ' . $reason);
    }
    
    return $timeAllowed && $sizeAllowed;
}

function downloadFile($url, $file) {
    global $curlExecutable;
    
    $tmpFile = $file . '.download';
    $command = sprintf('%s -L -s -o %s %s', escapeshellarg($curlExecutable), escapeshellarg($tmpFile), escapeshellarg($url));
    $return = -1;
    
    system($command, $return);
    
    if ($return != 0) {
        error("Error while executing command: {$command}");
        file_exists($tmpFile) && unlink($tmpFile);
        
        return false;
    } else {
        rename($tmpFile, $file);
        
        return true;
    }
}

function downloadYoutube($url, $file) {
    global $youtubeDlExecutable;
    
    $url = preg_replace('/&index=[^&]*/i', '', preg_replace('/&list=[^&]+/i', '', $url));
    $tmpFile = $file . '.download';
    $command = sprintf('%s --no-mtime --no-progress -f mp4 -o %s %s', escapeshellarg($youtubeDlExecutable), escapeshellarg($tmpFile), escapeshellarg($url));
    $return = -1;
    
    system($command, $return);
    
    if ($return != 0) {
        error("Error while executing command: {$command}");
        file_exists($tmpFile) && unlink($tmpFile);
        
        return false;
    } else {
        rename($tmpFile, $file);
        
        return true;
    }
}
