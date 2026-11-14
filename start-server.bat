@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  نظام متابعة مشاريع أمانة منطقة حائل
echo  افتح المتصفح على http://localhost:8080
echo  إن لم تُثبَّت القاعدة: http://localhost:8080/install.php
echo  تأكد أن MySQL يعمل من لوحة XAMPP
echo.
if exist "C:\xampp\php\php.exe" (
  "C:\xampp\php\php.exe" -S localhost:8080 -t "%~dp0"
) else (
  echo لم يُعثر على PHP في C:\xampp\php\php.exe
  pause
)
