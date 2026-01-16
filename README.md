#  Cerbero-PHP (Bunker & Stealth Edition v2.1)

> **Servidor de archivos ultra-seguro y ligero para Debian/Ubuntu.**
> Diseñado para Ethical Hacking, transferencia de archivos grandes (10GB+) y evasión de restricciones.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Platform](https://img.shields.io/badge/platform-Debian%20%7C%20Ubuntu-orange.svg)
![Security](https://img.shields.io/badge/security-Hardened-red.svg)

##  Descripción
Cerbero-PHP no es un simple gestor de archivos. Es una **bóveda de seguridad** diseñada para desplegarse en segundos.
A diferencia de otros scripts, Cerbero utiliza una arquitectura de "Cuarentena Total": los archivos subidos **nunca tocan la carpeta pública** del servidor web, haciendo imposible la ejecución de Web Shells o Malware vía URL.

###  Características Principales
* **🚀 Soporte Heavy-Duty:** Configurado para subir archivos de **10 GB** (Bypassea límites de RAM de PHP usando streaming a disco).
* ** Arquitectura Bunker:** Los archivos se guardan en `/var/www/cerbero_boveda`, una carpeta invisible para Internet.
* ** Modo Stealth:** Oculta archivos de sistema (`.htaccess`) y restringe el listado de directorios.
* ** Anti-Malware (Magic Bytes):** Bloquea ejecutables (.exe, .sh, .php, ELF) analizando su código binario, sin importar si les cambian el nombre.
* ** Instalación Automática:** Incluye un script `install.sh` que configura Apache, permisos y PHP en 10 segundos.

---

##  Instalación en 1 Minuto

No necesitas saber configurar servidores. Solo necesitas un VPS o máquina virtual con **Debian 13** (o Ubuntu 20.04+).

1.  **Clona el repositorio:**
    ```bash
    git clone https://github.com/Chelo2025/cerbero
    cd cerbero
    ```

2.  **Ejecuta el instalador maestro:**
    ```bash
    sudo chmod +x install.sh
    sudo ./install.sh
    ```

3.  **¡Listo!**
    Accede a tu navegador: `http://TU_IP_SERVIDOR`

---

##  Manual de Seguridad

### ¿Cómo subir scripts o herramientas (.sh, .php, .exe)?
Por seguridad, el sistema bloqueará cualquier archivo ejecutable puro (incluso si lo renombras a `.jpg`).
Para subir tus herramientas, **debes comprimirlas** primero:
* ✅ `herramienta.zip` (Permitido)
* ✅ `script.7z` (Permitido)
* ✅ `codigo.tar.gz` (Permitido)
* ❌ `virus.php` (Bloqueado por análisis de cabecera)

### Configurar Contraseña (Opcional)
Por defecto el sistema es abierto. Para ponerle contraseña:
1.  Edita el archivo: `sudo nano /var/www/html/index.php`
2.  Busca la línea: `'password' => '',`
3.  Escribe tu clave: `'password' => 'TuClaveSecreta123',`

---

##  Estructura del Sistema (Para Auditores)

El instalador crea la siguiente estructura blindada:

| Ruta | Descripción | Permisos |
| :--- | :--- | :--- |
| `/var/www/html/` | **Público.** Solo contiene el `index.php` (puerta de enlace). | 755 (www-data) |
| `/var/www/cerbero_boveda/` | **PRIVADO.** Aquí se guardan los archivos. Accesible solo por el sistema. | 770 (www-data) |
| `/var/www/cerbero_tmp/` | **Temporal.** Caché de subida para archivos gigantes (evita colapso de RAM). | 700 (www-data) |

---

##  Disclaimer
Este software ha sido creado con fines educativos y de administración de sistemas. El autor no se hace responsable del uso indebido de esta herramienta.

---
**Desarrollado por [Marcelo Martinez / Chelo2025]**
