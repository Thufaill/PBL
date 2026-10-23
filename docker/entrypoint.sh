#!/bin/sh
# Arahkan Apache ke port dari variabel PORT (Render/Railway/dll.), lalu jalankan di foreground.
set -e
PORT="${PORT:-80}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground
