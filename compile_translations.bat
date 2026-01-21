@echo off
REM ============================================
REM Script de compilation des fichiers .po en .mo
REM Windows (avec gettext installé)
REM ============================================

setlocal enabledelayedexpansion

REM Chemins
set LOCALES_DIR=%~dp0..\locales
set MSGFMT=msgfmt.exe

REM Vérifier si msgfmt existe
where /q %MSGFMT%
if errorlevel 1 (
    echo.
    echo *** ERREUR: msgfmt.exe non trouvé! ***
    echo.
    echo Veuillez installer gettext pour Windows:
    echo https://gnuwin32.sourceforge.net/packages/gettext.htm
    echo.
    echo Ou ajouter gettext au PATH (ex: C:\Program Files\GnuWin32\bin)
    echo.
    pause
    exit /b 1
)

echo Compilation des fichiers .po en .mo...
echo.

REM Compiler français
if exist "%LOCALES_DIR%\fr_FR\LC_MESSAGES\messages.po" (
    echo [Compilation] Français (fr_FR)...
    %MSGFMT% -o "%LOCALES_DIR%\fr_FR\LC_MESSAGES\messages.mo" "%LOCALES_DIR%\fr_FR\LC_MESSAGES\messages.po"
    if errorlevel 1 (
        echo ERREUR: Compilation française échouée!
        pause
        exit /b 1
    )
    echo OK - messages.mo créé
) else (
    echo ERREUR: %LOCALES_DIR%\fr_FR\LC_MESSAGES\messages.po non trouvé!
)
echo.

REM Compiler anglais
if exist "%LOCALES_DIR%\en_US\LC_MESSAGES\messages.po" (
    echo [Compilation] Anglais (en_US)...
    %MSGFMT% -o "%LOCALES_DIR%\en_US\LC_MESSAGES\messages.mo" "%LOCALES_DIR%\en_US\LC_MESSAGES\messages.po"
    if errorlevel 1 (
        echo ERREUR: Compilation anglaise échouée!
        pause
        exit /b 1
    )
    echo OK - messages.mo créé
) else (
    echo ERREUR: %LOCALES_DIR%\en_US\LC_MESSAGES\messages.po non trouvé!
)
echo.

echo ============================================
echo Compilation terminée avec succès!
echo.
echo Fichiers générés:
echo - %LOCALES_DIR%\fr_FR\LC_MESSAGES\messages.mo
echo - %LOCALES_DIR%\en_US\LC_MESSAGES\messages.mo
echo.
echo Vous pouvez maintenant utiliser _("texte") dans votre code PHP!
echo ============================================
echo.
pause
