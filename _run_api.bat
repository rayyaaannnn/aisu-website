@echo off 
cd /d %~dp0 
\"backend-php\php\php.exe\" -S localhost:8000 router.php
