#!/usr/bin/env bash
# Installs the tools Scrum needs on Ubuntu / cloud sessions (Linux counterpart of setup-machine.ps1).
# MariaDB is configured like XAMPP: user root, empty password, 127.0.0.1:3306.
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
SUDO=""; [ "$(id -u)" -ne 0 ] && SUDO="sudo"

if ! command -v mariadb >/dev/null; then
  $SUDO apt-get update -qq
  $SUDO apt-get install -y -qq mariadb-server mariadb-client
fi
$SUDO service mariadb start
sleep 3
$SUDO mariadb -uroot -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD(''); FLUSH PRIVILEGES;" 2>/dev/null || true

echo "==> Versions"
php -v | head -1
composer --version
node -v
git --version
mariadb -h127.0.0.1 -uroot -e "SELECT VERSION();" -N
php -m | grep -qiE '^(zip)$' && php -m | grep -qiE '^(gd)$' && php -m | grep -qiE '^(pdo_mysql)$' && echo "PHP extensions OK (zip, gd, pdo_mysql)"
