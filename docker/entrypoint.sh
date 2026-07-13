#!/bin/sh
set -e

# Render (y la mayoría de PaaS) inyectan el puerto real en $PORT — nginx
# debe escuchar ahí, no en un puerto fijo.
export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Cachear config/rutas/vistas usa las env vars ya inyectadas en tiempo de
# arranque del contenedor (no en build), así que sí refleja lo que Render
# tenga configurado en cada despliegue.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Idempotente: solo corre las migraciones pendientes. Sin acceso a shell en
# el plan gratis de Render, esta es la única vía práctica de migrar en cada
# despliegue.
php artisan migrate --force

php-fpm -D
exec nginx -g 'daemon off;'
