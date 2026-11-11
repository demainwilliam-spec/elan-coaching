#!/bin/bash
# Maintenance de chaque nuit en production (tâche cron Hostinger, cf. CLAUDE.md).
# Hostinger désactive proc_open : "php artisan schedule:run" ne peut pas lancer ses tâches,
# alors ce script lance lui-même les tâches quotidiennes de routes/console.php (à garder en phase),
# puis sauvegarde la base. À lancer avec : bash /chemin/vers/laravel/scripts/cron-quotidien.sh

# -u : une variable non définie arrête le script (évite par exemple un "rm" sur un chemin vide)
set -u
# Les fichiers créés (sauvegardes, fichier temporaire) ne sont lisibles que par ce compte
umask 077

# Dossier du projet : le parent du dossier qui contient ce script
APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
# Le cron a un PATH réduit : on cherche php, sinon chemin habituel du serveur
PHP="$(command -v php || echo /usr/bin/php)"
BACKUP_DIR="$HOME/sauvegardes"
BACKUP_DAYS=14

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

cd "$APP_DIR" || exit 1

# 1. Tâches quotidiennes : on continue même si l'une échoue, mais on le note
for task in "model:prune" "queue:prune-failed --hours=168" "auth:clear-resets clients" "auth:clear-resets users" "clients:warn-inactive"; do
    # $task sans guillemets : "auth:clear-resets clients" doit devenir deux arguments
    if "$PHP" artisan $task --no-interaction > /dev/null 2>&1; then
        log "OK     $task"
    else
        log "ECHEC  $task"
    fi
done

# 2. Sauvegarde de la base, avec les identifiants du .env (ils restent à un seul endroit)
env_value() {
    # Dernière ligne "CLE=valeur" du .env, sans les guillemets autour de la valeur
    grep -E "^$1=" .env | tail -n 1 | cut -d '=' -f 2- | sed -E 's/^"(.*)"$/\1/; s/^'"'"'(.*)'"'"'$/\1/'
}

DB_NAME="$(env_value DB_DATABASE)"
DB_USER="$(env_value DB_USERNAME)"
DB_PASS="$(env_value DB_PASSWORD)"

mkdir -p "$BACKUP_DIR"

# Le mot de passe passe par un fichier temporaire (lisible par ce compte seulement)
# plutôt que par la ligne de commande, où d'autres processus pourraient le voir
CREDENTIALS="$(mktemp)"
# trap : supprime ce fichier à la fin du script, même en cas d'erreur
trap 'rm -f "$CREDENTIALS"' EXIT
# Dans ce format, \ et " doivent être échappés à l'intérieur des guillemets
ESCAPED_PASS="$(printf '%s' "$DB_PASS" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g')"
printf '[client]\nuser="%s"\npassword="%s"\n' "$DB_USER" "$ESCAPED_PASS" > "$CREDENTIALS"

FILE="$BACKUP_DIR/jfcoaching-$(date '+%Y-%m-%d-%H%M').sql.gz"

# pipefail : la commande échoue si mysqldump échoue, pas seulement si gzip échoue
set -o pipefail
# --single-transaction : une copie cohérente sans bloquer le site pendant la sauvegarde
if mysqldump --defaults-extra-file="$CREDENTIALS" --single-transaction --quick --no-tablespaces \
        --default-character-set=utf8mb4 "$DB_NAME" | gzip > "$FILE"; then
    log "OK     sauvegarde $(basename "$FILE") ($(du -h "$FILE" | cut -f 1))"
else
    rm -f "$FILE" # pas de sauvegarde à moitié écrite
    log "ECHEC  sauvegarde de la base"
fi

# On ne garde que les sauvegardes des $BACKUP_DAYS derniers jours
find "$BACKUP_DIR" -name 'jfcoaching-*.sql.gz' -mtime +$((BACKUP_DAYS - 1)) -delete
