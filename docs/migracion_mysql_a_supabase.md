# Migración de MySQL/phpMyAdmin a Supabase

## Objetivo

Reemplazar la base local de XAMPP/MySQL por una base de datos cloud en Supabase PostgreSQL, manteniendo el mismo modelo copo de nieve que ya tenías en phpMyAdmin.

## Flujo final

```text
CSV → Web PHP → Supabase PostgreSQL → Google Colab IA → Google Sheets → Looker Studio
```

## Paso 1. Crear el proyecto en Supabase

Entra a Supabase y crea un proyecto llamado:

```text
smartcampus
```

Guarda la contraseña del proyecto.

## Paso 2. Crear las tablas iguales a MySQL

Abre:

```text
Supabase → SQL Editor → New query
```

Pega el contenido completo de:

```text
database/01_supabase_sql_editor_pegar_aqui.sql
```

Ese archivo crea las mismas tablas:

```text
dimambiente
dimedificio
dimocupacion
dimtiempo
factconsumoenergetico
fact_consumo_energetico_pred
```

También carga los datos originales exportados desde phpMyAdmin.

## Paso 3. Revisar RLS para la demo

Para una sustentación académica rápida, puedes desactivar RLS temporalmente en las tablas del proyecto o crear políticas que permitan `select`, `insert`, `update` y `delete` desde la API.

Tablas que usa la página:

```text
staging_upload_batch
staging_lectura_cruda
dimambiente
dimedificio
dimocupacion
dimtiempo
factconsumoenergetico
fact_consumo_energetico_pred
```

## Paso 4. Copiar credenciales de Supabase

En Supabase entra a:

```text
Settings → API
```

Copia:

```text
Project URL
anon public key
```

Luego abre:

```text
backend/app/config/ExternalServices.php
```

Configura:

```php
'SUPABASE_URL' => 'https://TU-PROYECTO.supabase.co',
'SUPABASE_API_KEY' => 'TU_ANON_KEY',
```

## Paso 5. Probar la página

Coloca el proyecto dentro de:

```text
C:\xampp\htdocs\smart-campus
```

Abre:

```text
http://localhost:8081/smart-campus/frontend/public/index.php
```

Sube un CSV y presiona **Continuar** paso por paso. La página ya no insertará en MySQL local; insertará en Supabase.

## Paso 6. Ejecutar Colab

Abre:

```text
scripts_colab/PROCESO_5_Y_6_SUPABASE.ipynb
```

Ejecuta las celdas y copia la URL pública de ngrok. Luego pégala en:

```php
'COLAB_API_URL' => 'https://TU_URL_NGROK.ngrok-free.app',
```

## Paso 7. Google Sheets y Looker Studio

Crea una hoja de cálculo en Google Sheets. Luego crea Apps Script con:

```text
google_apps_script/Code.gs
```

Publica como Web App y pega la URL en:

```php
'GOOGLE_SHEETS_WEBAPP_URL' => 'https://script.google.com/macros/s/.../exec',
```

Finalmente conecta Looker Studio a esa hoja y pega la URL del reporte en:

```php
'LOOKER_STUDIO_URL' => 'https://lookerstudio.google.com/reporting/...',
```

## Texto para sustentación

> Se reemplazó la base local MySQL/phpMyAdmin por Supabase PostgreSQL en la nube, manteniendo intacto el modelo dimensional copo de nieve. La aplicación web conserva su interfaz de siete etapas, pero ahora la carga, validación, ETL, almacenamiento de hechos y almacenamiento predictivo operan en Supabase. Luego, el dataset consolidado es enviado a Google Colab para Machine Learning, los resultados se publican en Google Sheets y Looker Studio consume esa hoja como dashboard cloud.
