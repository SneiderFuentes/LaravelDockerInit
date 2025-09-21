#!/usr/bin/env bash
set -euo pipefail

# ===== Configuración =====
TG_BOT_TOKEN="${TG_BOT_TOKEN:-}"
TG_CHAT_IDS="${TG_CHAT_IDS:-}"
# Contenedores a ignorar (separados por coma): ej. "appointments_ngrok,watcher"
IGNORE_CONTAINERS="${IGNORE_CONTAINERS:-}"
# 0=notificaciones solo unhealthy/stop; 1=todo (incluye healthy/start)
VERBOSE="${VERBOSE:-0}"

# ===== Funciones auxiliares =====
contains() {
    case ",$1," in
        *",${2},"*) return 0;;
        *) return 1;;
    esac
}

notify_telegram() {
    local text="$1"
    [ -z "$TG_BOT_TOKEN" ] || [ -z "$TG_CHAT_IDS" ] && return 0

    # Convertir la cadena de chat_ids separados por coma en un array
    IFS=',' read -ra CHAT_ARRAY <<< "$TG_CHAT_IDS"

    # Enviar mensaje a cada chat_id
    for chat_id in "${CHAT_ARRAY[@]}"; do
        # Limpiar espacios en blanco
        chat_id=$(echo "$chat_id" | xargs)
        [ -z "$chat_id" ] && continue

        curl -s "https://api.telegram.org/bot${TG_BOT_TOKEN}/sendMessage" \
            -d chat_id="$chat_id" \
            --data-urlencode text="$text" >/dev/null || true
    done
}

notify_all() {
    local msg="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] $msg"
    notify_telegram "$msg"
}

# ===== Inicio del watcher =====
notify_all "🔔 Docker Watcher iniciado (modo eventos) - Proyecto: my-appointments"

# Escuchar eventos de Docker en tiempo real
# health_status: healthy / unhealthy
# status: start / stop / die / restart
docker events --format '{{json .}}' | while read -r line; do
    # Parse del JSON del evento
    status=$(echo "$line" | jq -r '.status // empty')
    type=$(echo "$line"   | jq -r '.Type // empty')
    name=$(echo "$line"   | jq -r '.Actor.Attributes.name // empty')
    health=$(echo "$line" | jq -r '.Actor.Attributes.health_status // empty')

    # Solo procesar eventos de contenedores
    [ "$type" != "container" ] && continue
    [ -z "$name" ] && continue

    # Ignorar contenedores especificados
    if [ -n "$IGNORE_CONTAINERS" ] && contains "$IGNORE_CONTAINERS" "$name"; then
        continue
    fi

    # Procesar eventos según el tipo
    case "$status" in
        health_status)
            # Cambio de estado de salud: healthy/unhealthy
            if [ "$health" = "unhealthy" ]; then
                notify_all "🚑 *UNHEALTHY*: \`$name\` - Requiere atención inmediata"
            elif [ "$health" = "healthy" ] && [ "$VERBOSE" = "1" ]; then
                notify_all "✅ *HEALTHY*: \`$name\` - Servicio recuperado"
            fi
            ;;
        start)
            [ "$VERBOSE" = "1" ] && notify_all "▶️ *STARTED*: \`$name\`"
            ;;
        die|stop)
            notify_all "🛑 *STOPPED*: \`$name\` - Servicio caído"
            ;;
        restart)
            notify_all "🔁 *RESTART*: \`$name\` - Servicio reiniciado"
            ;;
        kill)
            notify_all "💀 *KILLED*: \`$name\` - Proceso terminado forzosamente"
            ;;
        oom)
            notify_all "💥 *OUT OF MEMORY*: \`$name\` - Sin memoria disponible"
            ;;
    esac
done
