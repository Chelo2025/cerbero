#!/bin/bash

# ==========================================
# INSTALADOR CERBERO V2.0 - BUNKER EDITION
# ==========================================

# Colores
G='\033[0;32m' # Verde
R='\033[0;31m' # Rojo
Y='\033[1;33m' # Amarillo
NC='\033[0m'   # Sin color

echo -e "${G}>>> INICIANDO INSTALACIÓN DE CERBERO BUNKER...${NC}"

# 1. Verificar Superusuario
if [ "$EUID" -ne 0 ]; then
  echo -e "${R}[ERROR] Debes ejecutar esto como root (sudo ./install.sh)${NC}"
  exit 1
fi

# 2. Instalar Dependencias (Apache + PHP)
echo -e "${Y}[1/5] Instalando servidor web y PHP...${NC}"
apt update -qq
apt install apache2 libapache2-mod-php php-cli -y -qq

# 3. Estructura de Carpetas (SEGURIDAD CRÍTICA)
echo -e "${Y}[2/5] Creando boveda aislada fuera del web root...${NC}"

# Definir rutas
WEB_ROOT="/var/www/html"
SAFE_DIR="/var/www/cerbero_boveda"
TMP_DIR="/var/www/cerbero_tmp"

# Limpiar y crear
rm -f $WEB_ROOT/index.html
mkdir -p $SAFE_DIR
mkdir -p $TMP_DIR

# Copiar el código
if [ -f "index.php" ]; then
    cp index.php $WEB_ROOT/index.php
    echo -e "${G}    -> Código copiado exitosamente.${NC}"
else
    echo -e "${R}[ERROR] No encuentro el archivo index.php en esta carpeta.${NC}"
    exit 1
fi

# 4. Permisos y Hardening (Anti-VoidLink)
echo -e "${Y}[3/5] Aplicando permisos de grado militar...${NC}"

# Asignar dueño a Apache
chown -R www-data:www-data $SAFE_DIR
chown -R www-data:www-data $TMP_DIR
chown -R www-data:www-data $WEB_ROOT

# Permisos 770: Solo Apache y Root pueden entrar. Internet NO puede entrar.
chmod -R 770 $SAFE_DIR
chmod -R 700 $TMP_DIR

# Capa Extra: .htaccess dentro de la boveda (Por si alguien mueve la carpeta por error)
cat <<EOF > $SAFE_DIR/.htaccess
# BLOQUEO TOTAL DE EJECUCIÓN
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
ForceType application/octet-stream
Options -Indexes -ExecCGI -FollowSymLinks
Require all denied
EOF
echo -e "${G}    -> Escudo .htaccess activado en la boveda.${NC}"

# 5. Configuración Automática de PHP (Soporte 10GB)
echo -e "${Y}[4/5] Hackeando php.ini para soportar 10 GB...${NC}"

# Detectar php.ini activo
PHP_INI=$(php -i | grep "Configuration File (php.ini) Path" | awk '{print $6}')/apache2/php.ini

if [ -f "$PHP_INI" ]; then
    # Backup por seguridad
    cp $PHP_INI "$PHP_INI.bak"
    
    # Inyección de configuración
    sed -i 's/^upload_max_filesize.*/upload_max_filesize = 10G/' $PHP_INI
    sed -i 's/^post_max_size.*/post_max_size = 10G/' $PHP_INI
    sed -i 's/^memory_limit.*/memory_limit = 512M/' $PHP_INI
    sed -i 's/^max_execution_time.*/max_execution_time = 0/' $PHP_INI
    
    # Configurar carpeta temporal segura (Evita Out of Memory)
    # Comentar la línea si existe para evitar duplicados y agregar la nuestra
    sed -i '/^upload_tmp_dir/d' $PHP_INI
    echo "upload_tmp_dir = $TMP_DIR" >> $PHP_INI
    
    echo -e "${G}    -> php.ini configurado en: $PHP_INI${NC}"
else
    echo -e "${R}[ERROR] No pude encontrar el php.ini automáticamente.${NC}"
fi

# 6. Reiniciar Servicios
echo -e "${Y}[5/5] Reiniciando Apache...${NC}"
systemctl restart apache2

echo -e "${G}==============================================${NC}"
echo -e "${G}     ¡INSTALACIÓN COMPLETADA CON ÉXITO!      ${NC}"
echo -e "${G}==============================================${NC}"
echo -e "Accede a tu servidor seguro aquí:"
echo -e " http://$(hostname -I | cut -d' ' -f1)"
echo -e ""
echo -e "Tus archivos se guardan en: $SAFE_DIR"
echo -e "(Esta carpeta es invisible para internet)"
