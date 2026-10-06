@echo off
title PackPoint Starter
echo ==========================================
echo  PackPoint wordt gestart...
echo ==========================================

:: Eerst zoeken we XAMPP. We kijken op D: en anders op C:.
set "XAMPP="
if exist "D:\xampp\php\php.exe" set "XAMPP=D:\xampp"
if not defined XAMPP if exist "C:\xampp\php\php.exe" set "XAMPP=C:\xampp"

:: PackPoint heeft MySQL nodig. Draait die nog niet? Dan starten we hem.
if defined XAMPP (
    tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
    if errorlevel 1 (
        echo MySQL wordt gestart...
        start "PackPoint MySQL" /min "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
        rem Even wachten, MySQL heeft een paar seconden nodig om op te starten.
        timeout /t 4 /nobreak >nul
    ) else (
        echo MySQL draait al.
    )
)

:: Dan starten we de website vanuit de map 'public' en openen we hem in de browser.
cd /d "%~dp0public"
start "" "http://localhost:8000"

if defined XAMPP (
    "%XAMPP%\php\php.exe" -S localhost:8000
) else (
    rem Geen XAMPP gevonden. Dan proberen we de PHP die op de computer staat.
    rem Zorg er in dat geval zelf voor dat MySQL draait.
    php -S localhost:8000
)
