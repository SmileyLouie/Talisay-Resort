@echo off
title Talisay Resort - automation
cd /d "%~dp0"
echo Running the resort automation scheduler. Keep this window open.
echo Unpaid holds, no-shows, check-outs, reminders and cleanup run on their own.
"C:\php-8.5.1\php.exe" artisan schedule:work
