@echo off
:: Bot holatini tekshirish va boshqarish menyusi
title ZiyoCRM BOT - Boshqaruv

:menu
cls
echo.
echo  ╔════════════════════════════════════════╗
echo  ║    🏫 ZiyoCRM BOT BOSHQARUV    ║
echo  ╚════════════════════════════════════════╝
echo.

:: Bot ishlayaptimi tekshirish
tasklist /FI "IMAGENAME eq php.exe" 2>NUL | find /I /N "php.exe" >NUL
if "%ERRORLEVEL%"=="0" (
    echo  ✅ Bot holati: ISHLAYAPTI
) else (
    echo  ❌ Bot holati: TO'XTAGAN
)

echo.
echo  1. ▶️  Botni ishga tushirish
echo  2. ⏹️  Botni to'xtatish
echo  3. 🔄 Botni qayta ishga tushirish
echo  4. 📋 PHP jarayonlarini ko'rish
echo  5. 🚪 Chiqish
echo.
set /p choice="Tanlang (1-5): "

if "%choice%"=="1" goto start_bot
if "%choice%"=="2" goto stop_bot
if "%choice%"=="3" goto restart_bot
if "%choice%"=="4" goto show_procs
if "%choice%"=="5" exit
goto menu

:start_bot
tasklist /FI "IMAGENAME eq php.exe" 2>NUL | find /I /N "php.exe" >NUL
if "%ERRORLEVEL%"=="0" (
    echo  ⚠️  Bot allaqachon ishlayapti!
) else (
    start /min "" "C:\php\php.exe" "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\bot\polling.php"
    timeout /t 2 /nobreak >NUL
    echo  ✅ Bot ishga tushirildi!
)
pause
goto menu

:stop_bot
taskkill /F /IM php.exe >NUL 2>&1
echo  ⏹️  Bot to'xtatildi.
pause
goto menu

:restart_bot
taskkill /F /IM php.exe >NUL 2>&1
timeout /t 1 /nobreak >NUL
start /min "" "C:\php\php.exe" "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\bot\polling.php"
timeout /t 2 /nobreak >NUL
echo  🔄 Bot qayta ishga tushirildi!
pause
goto menu

:show_procs
echo.
tasklist /FI "IMAGENAME eq php.exe"
pause
goto menu
