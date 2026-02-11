@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo 正在扫描图片文件...

REM 删除已存在的list.txt
if exist "list.txt" del "list.txt"

REM 设置支持的图片格式扩展名
set "extensions=*.png *.jpg *.jpeg *.gif *.bmp *.tif *.tiff *.webp *.ico"

REM 遍历所有支持的图片文件
for /f "delims=" %%i in ('dir /b /s /a-d %extensions% 2^>nul') do (
    REM 获取相对路径
    set "fullpath=%%i"
    set "relpath=!fullpath:%cd%\=!"
    
    REM 替换反斜杠为正斜杠（可选，根据您的需要）
    set "relpath=!relpath:\=/!"
    
    REM 输出到控制台和文件
    echo !relpath!
    echo !relpath!>>"list.txt"
)

echo.
echo 图片列表已更新到 list.txt
echo 3秒后自动关闭...
timeout /t 3 /nobreak >nul
exit