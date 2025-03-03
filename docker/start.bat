@echo off
setlocal enabledelayedexpansion

echo === OpenSTAManager Docker Manager ===
echo.

REM Verifica se Docker è in esecuzione
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo ERRORE: Docker non è in esecuzione.
    echo Avviare Docker Desktop e riprovare.
    pause
    exit /b 1
)

:menu
cls
echo === OpenSTAManager Docker Manager ===
echo.
echo 1. Avvia i container
echo 2. Ferma i container
echo 3. Visualizza stato container
echo 4. Visualizza logs
echo 5. Esci
echo.
set /p scelta="Seleziona un'opzione (1-5): "

if "%scelta%"=="1" (
    echo.
    echo Avvio dei container in corso...
    docker-compose up -d
    echo.
    echo Container avviati! OpenSTAManager è accessibile su http://localhost:8090
    pause
    goto menu
)

if "%scelta%"=="2" (
    echo.
    echo Arresto dei container in corso...
    docker-compose down
    echo Container arrestati.
    pause
    goto menu
)

if "%scelta%"=="3" (
    echo.
    echo Stato attuale dei container:
    docker-compose ps
    pause
    goto menu
)

if "%scelta%"=="4" (
    echo.
    echo Logs dei container:
    docker-compose logs
    pause
    goto menu
)

if "%scelta%"=="5" (
    echo.
    echo Uscita in corso...
    exit /b 0
)

echo.
echo Opzione non valida. Riprovare.
timeout /t 2 >nul
goto menu
