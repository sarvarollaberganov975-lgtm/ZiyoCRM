@echo off
title ZiyoCRM BOT
echo.
echo  ========================================
echo    ZiyoCRM BOT - Fon rejimi
echo  ========================================
echo.
php "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\bot\polling.php"
echo.
echo  Bot to'xtadi! Qayta ishga tushirish uchun...
timeout /t 5
goto :start
:start
php "C:\Users\user\.gemini\antigravity\scratch\ZiyoCRM\bot\polling.php"
