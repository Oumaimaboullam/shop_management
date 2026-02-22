@echo off
echo Starting Shop Management System...
echo Please ensure XAMPP is installed in C:\xampp

cd /d C:\xampp

:: Start XAMPP Control Panel minimized (optional, usually you want the services to run)
:: It's better to ensure Apache and MySQL are installed as services so they start with Windows
:: But this script can also try to start them if they are not running.

echo Starting Apache...
start /min apache\bin\httpd.exe

echo Starting MySQL...
start /min mysql\bin\mysqld.exe

echo Waiting for services to initialize...
timeout /t 5 > nul

echo Opening Application...
start http://localhost/shop_management/login.php

exit