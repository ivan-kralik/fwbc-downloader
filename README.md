# FWBC Sermons Downloader
Script for downloading of sermons from FWBC page

## Configuration
Edit `config.php` according to comments

## Usage
Simply run `php download.php`. You can create cron job for that.
The program will run only once at the time, an attempt to run second instance of `download.php` will fail,
so don't worry about cron timing.

By default, the program prints logs to stderr.
If you want logging to file, simply use stderr redirect operator, e.g.

`php download.php 2> logfile.log`
