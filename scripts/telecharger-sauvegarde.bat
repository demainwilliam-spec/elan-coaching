@echo off
rem Télécharge sur ce PC la dernière sauvegarde de la base du site (copie hors du serveur).
rem À lancer une fois par mois par un double-clic. Utilise la connexion SSH "jfcoaching" (clé dans %USERPROFILE%\.ssh).
rem Les fichiers contiennent des données personnelles des clients : dossier privé uniquement, jamais sur GitHub.

rem Affichage des accents dans la fenêtre
chcp 65001 >nul
setlocal

set "DEST=%USERPROFILE%\Documents\Sauvegardes JF Coaching"
if not exist "%DEST%" mkdir "%DEST%"

echo Recherche de la dernière sauvegarde sur le serveur...
set "FICHIER="
rem for /f : récupère le nom du fichier le plus récent renvoyé par le serveur (ls -t = du plus récent au plus ancien) ;
rem dans for /f, cmd lit "=" comme un séparateur : il faut l'écrire ^=
for /f "usebackq delims=" %%F in (`ssh -o BatchMode^=yes jfcoaching "ls -t ~/sauvegardes/jfcoaching-*.sql.gz 2>/dev/null | head -1" 2^>nul`) do set "FICHIER=%%F"

if not defined FICHIER (
    echo.
    echo ECHEC : impossible de joindre le serveur ou aucune sauvegarde trouvée.
    echo Vérifiez la connexion internet, puis réessayez.
    goto fin
)

rem pas de \ à la fin de "%DEST%" : \" serait lu comme un guillemet échappé
scp -q "jfcoaching:%FICHIER%" "%DEST%" 2>nul
if errorlevel 1 (
    echo.
    echo ECHEC du téléchargement de %FICHIER%
    goto fin
)

echo.
echo OK : sauvegarde téléchargée dans "%DEST%"
echo Contenu du dossier :
dir /b /o-n "%DEST%\*.sql.gz"

:fin
echo.
pause
