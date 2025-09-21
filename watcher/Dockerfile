FROM alpine:3.20

# Instalar dependencias necesarias
RUN apk add --no-cache bash curl jq docker-cli

# Copiar el script de monitoreo
COPY healthwatch.sh /usr/local/bin/healthwatch.sh

# Dar permisos de ejecución
RUN chmod +x /usr/local/bin/healthwatch.sh

# Punto de entrada
ENTRYPOINT ["/usr/local/bin/healthwatch.sh"]
