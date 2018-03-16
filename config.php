<?php

// Url with FWBC sermons
$url = 'http://www.faithfulwordbaptist.org/page5.html';

// Target directory where sermons are saved
$dir = $argc < 2 ? getcwd() : $argv[1];

// Daily download limit
$dailyLimit = 2000 * 1024 * 1024; // 2000 MB

// Time when downloading is allowed
$allowFrom = '00:00';
$allowTo = '23:59';

// Path to curl executable
$curlExecutable = 'curl';

// Path to youtube-dl executable
$youtubeDlExecutable = 'youtube-dl';
