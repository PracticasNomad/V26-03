# TheNomadApp - V25-26

### Desarrolladores: Adri, Alex, Héctor y Óscar

Solucionar errores y planificar sprints

## Requisitos Previos

Antes de comenzar, asegúrate de tener instalado en tu máquina:

* [PHP](https://www.php.net/downloads) (versión 8.1 o superior recomendada)
* [Composer](https://getcomposer.org/download/)

## Instalación y Configuración

Sigue estos pasos para levantar el entorno de desarrollo local:

### 1. Clona el repositorio

```bash
git clone https://github.com/tu-usuario/tu-repo.git
cd tu-repo
```

### 2. Instala las dependencias de Composer

```bash
composer install
```

### 3. Configura el entorno

Copia el archivo de ejemplo para crear tu propio archivo de entorno local:

```bash
cp .env.example .env
```

> **Nota:** Abre el archivo `.env` recién creado y configura tus credenciales de base de datos u otras variables necesarias.

---

## Arrancar el Servidor

Para este proyecto puedes usar el servidor interno de PHP para desarrollo. Ejecuta el siguiente comando en la raíz del proyecto:

```bash
php -S localhost:8000
```

Abre tu navegador y visita:

```
http://localhost:8000
```

¡El proyecto debería estar funcionando!

---

## Estructura del Proyecto

```
src/       - Código fuente principal de la aplicación.
vendor/    - Dependencias gestionadas por Composer (ignorado en Git).
public/    - Archivos accesibles públicamente (ej. index.php, CSS, JS).
```

> **Nota:** Si tu proyecto usa una carpeta `public/` como punto de entrada (muy común), el comando de arranque debería ser:

```bash
php -S localhost:8000 -t public/
```
