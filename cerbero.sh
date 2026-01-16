#!/bin/bash

# Colores para que se vea bonito
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}=== INSTALADOR AUTOMÁTICO DE CERBERO-PHP ===${NC}"

# 1. Verificar si es root
if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}Por favor, ejecuta este script como root (sudo ./install.sh)${NC}"
  exit
fi

# 2. Instalar Dependencias
echo -e "${GREEN}[1/5] Instalando Apache y PHP...${NC}"
apt update
apt install apache2 libapache2-mod-php php-cli -y

# 3. Configurar Directorios y Permisos (Hardening)
echo -e "${GREEN}[2/5] Configurando carpetas seguras...${NC}"
mkdir -p /var/www/html/archivos
# Carpeta temporal fuera del alcance público (Seguridad)
mkdir -p /var/www/tmp_cache 

# Copiar el archivo index.php
if [ -f "index.php" ]; then
    cp index.php /var/www/html/index.php
    rm /var/www/html/index.html 2>/dev/null
else
    echo -e "${RED}Error: No encuentro index.php en esta carpeta.${NC}"
    exit 1
fi

# Asignar permisos estrictos (Solo Apache puede tocar esto)
chown -R www-data:www-data /var/www/html/archivos
chown -R www-data:www-data /var/www/tmp_cache
chmod -R 770 /var/www/html/archivos
chmod -R 700 /var/www/tmp_cache

# 4. Seguridad Anti-Scripts (Evitar hacking)
echo -e "${GREEN}[3/5] Aplicando parches de seguridad...${NC}"
cat <<EOF > /var/www/html/archivos/.htaccess
# Bloquear ejecución de PHP en la carpeta de subidas
<FilesMatch "\.(php|php5|phtml|pl|py|cgi|sh)$">
    Require all denied
</FilesMatch>
Options -Indexes
EOF

# 5. Configurar PHP.ini Automáticamente (La parte difícil)
echo -e "${GREEN}[4/5] Configurando PHP para soportar 10 GB...${NC}"

# Buscar el php.ini activo de Apache
PHP_INI=$(php -i | grep "Configuration File (php.ini) Path" | awk '{print $6}')/apache2/php.ini

if [ -f "$PHP_INI" ]; then
    # Usamos sed para reemplazar los valores automáticamente
    sed -i 's/^upload_max_filesize.*/upload_max_filesize = 10G/' $PHP_INI
    sed -i 's/^post_max_size.*/post_max_size = 10G/' $PHP_INI
    sed -i 's/^memory_limit.*/memory_limit = 512M/' $PHP_INI
    # Descomentar y configurar la carpeta temporal
    sed -i 's|^;upload_tmp_dir.*|upload_tmp_dir = /var/www/tmp_cache|' $PHP_INI
    sed -i 's|^upload_tmp_dir.*|upload_tmp_dir = /var/www/tmp_cache|' $PHP_INI
    
    echo "PHP configurado en: $PHP_INI"
else
    echo -e "${RED}No se pudo encontrar php.ini automáticamente. Revisa el README.${NC}"
fi

# 6. Reiniciar Apache
echo -e "${GREEN}[5/5] Reiniciando servidor...${NC}"
systemctl restart apache2

echo -e "${GREEN}==============================================${NC}"
echo -e "${GREEN}¡INSTALACIÓN COMPLETADA!${NC}"
echo -e "Accede a: http://$(hostname -I | cut -d' ' -f1)"
echo -e "${GREEN}==============================================${NC}"
