@echo off
title PackPoint Starter
echo ==========================================
echo  PackPoint wordt gestart...
echo ==========================================

:: ------------------------------------------------------------
:: 1. Zoek XAMPP (eerst op D:, dan op C:)
:: ------------------------------------------------------------
set "XAMPP="
if exist "D:\xampp\php\php.exe" set "XAMPP=D:\xampp"
if not defined XAMPP if exist "C:\xampp\php\php.exe" set "XAMPP=C:\xampp"

:: ------------------------------------------------------------
:: 2. Start MySQL als die nog niet draait
::    (PackPoint gebruikt een MySQL database)
:: ------------------------------------------------------------
if defined XAMPP (
    tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
    if errorlevel 1 (
        echo MySQL wordt gestart...
        start "PackPoint MySQL" /min "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
        rem Even wachten tot MySQL klaar is
        timeout /t 4 /nobreak >nul
    ) else (
        echo MySQL draait al.
    )
)

:: ------------------------------------------------------------
:: 3. Start de PHP webserver vanuit de map 'public'
:: ------------------------------------------------------------
cd /d "%~dp0public"
start "" "http://localhost:8000"

if defined XAMPP (
    "%XAMPP%\php\php.exe" -S localhost:8000
) else (
    rem Geen XAMPP gevonden: probeer de PHP die op de computer is geinstalleerd
    rem (zorg dan zelf dat MySQL draait)
    php -S localhost:8000
)
