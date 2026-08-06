@echo off
title ZiyoCRM Server
color 0A

echo.
echo =====================================================
echo ZiyoCRM - Talim markazi tizimi
echo =====================================================
echo.

echo ZiyoCRM serveri ishga tushirilmoqda...
start http://localhost:8080/index.php

echo.
echo =====================================================
echo ZiyoCRM serveri ishlamoqda!
echo Sayt manzili: http://localhost:8080
echo Tizimni yopish uchun ushbu oynani yoping.
echo =====================================================
echo.

C:\php\php.exe -S 0.0.0.0:8080 -t "%~dp0"
