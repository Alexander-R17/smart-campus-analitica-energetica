<<<<<<< HEAD
# Smart Campus MVC v4 — Universidad Nueva Esperanza

Versión con flujo 1 al 7, carga CSV, Data Warehouse en MySQL, KPIs, predicciones, filtros OLAP y exportación.

## Configuración usada

- Base de datos: `smartcampus`
- Host: `localhost`
- Usuario: `root`
- Contraseña: vacía
- Puerto MySQL: `3309`
- Apache: `8081`

## Cómo ejecutar

1. Copia la carpeta `smart-campus-mvc-v4` dentro de:

```text
C:\xampp\htdocs\
```

2. Inicia Apache y MySQL en XAMPP.

3. Abre:

```text
http://localhost:8081/smart-campus-mvc-v4/public/
```

4. Acceso:
=======
# Smart Campus v10 — Universidad Nueva Esperanza

Versión v10 del prototipo web de analítica energética para Smart Campus.

Esta versión mantiene la **estructura modular de la v9** solicitada por el docente y recupera la **estética visual de la v8**:

- Landing institucional.
- Login mejorado con logo.
- Flujo lineal 1 al 7 en una sola fila.
- Íconos de IoT, Staging, ETL y Data Warehouse.
- Animaciones por etapa.
- Notificaciones visuales propias, sin alertas molestas del navegador.
- Conexión con Google Colab para Capa IA y Capa Semántica.
- Guardado de CSV procesado para Power BI.
- Guardado de registros procesados en MySQL.
- Botón Ver datos actuales.
- Botón Graficar para abrir Power BI Desktop.

## Estructura

```text
smart-campus-v10/
├── frontend/
│   ├── public/
│   └── assets/
│       ├── css/
│       ├── js/
│       └── img/
├── backend/
│   └── app/
│       ├── config/
│       ├── controllers/
│       └── models/
├── database/
├── scripts_colab/
├── storage/
├── powerbi/
├── data/
└── docs/
```

## Instalación rápida

1. Copiar la carpeta en:

```text
C:\xampp\htdocs\smart-campus-v10
```

2. Iniciar Apache y MySQL desde XAMPP.

3. Crear la base de datos usando:

```text
database/schema.sql
```

4. Revisar configuración:

```text
backend/app/config/ExternalServices.php
```

Ahí se configura:

- URL de Google Colab / ngrok.
- Puerto MySQL.
- Ruta del CSV procesado.
- Ruta del archivo Power BI.

5. Abrir en navegador:

```text
http://localhost/smart-campus-v10/
```

Si Apache usa puerto 8081:

```text
http://localhost:8081/smart-campus-v10/
```

## Credenciales
>>>>>>> 30aeb43 (Actualización V10: Smart Campus con Power BI y Google Colab integrados)

```text
Usuario: Ingeniero1
Contraseña: 12345678
```

<<<<<<< HEAD
## Dataset de prueba con mínimo 1000 registros

Usa primero este archivo:

```text
data/dataset_smart_campus_1000.csv
```

Tiene 1000 registros simulados y normalizados con las columnas:

```text
id_tiempo,hora,mes,anio,id_edificio,id_ambiente,ocupacion,temperatura,demanda_pico_kw,factor_potencia,consumo_kwh
```

## Filtros OLAP agregados

En la vista de gráficas puedes filtrar por:

- Año
- Mes
- Edificio
- Tipo de ambiente
- Riesgo energético
- Rango de horas
- Consumo mínimo

También incluye botones OLAP:

- SLICE
- DICE
- DRILL DOWN
- ROLL UP
- PIVOT

Los filtros modifican KPIs, gráficas, tabla y exportación Power BI.

## Importante

Los registros cargados quedan guardados en MySQL para análisis histórico. El botón `Retroceder` del flujo limpia la tabla de hechos para volver a cargar datos desde cero.


## Ajuste V5: Ver datos actuales

Se agregó el botón **📊 Ver datos actuales** en la parte superior de la vista del proceso. Este botón abre directamente el dashboard de gráficas usando los registros ya guardados en MySQL, sin necesidad de volver a subir el CSV. Así se evita duplicar información cuando solo se desea consultar KPIs, predicciones, filtros OLAP y reportes.
=======
## Prueba rápida

Puedes usar el CSV de prueba:

```text
data/smartcampus_dataset_prueba_1000.csv
```

También puedes subir 2 CSV de 500 registros; la web los consolida en:

```text
storage/uploads/ultimo_dataset.csv
```

## Flujo esperado

```text
1. Subir CSV
2. Staging Area
3. ETL
4. Data Warehouse
5. Google Colab / Machine Learning
6. Capa Semántica / KPIs
7. Power BI Desktop
```

## Power BI

Coloca tu archivo `.pbix` aquí:

```text
powerbi/report/smartcampus_reporte.pbix
```

El CSV que debe leer Power BI es:

```text
powerbi/data/smartcampus_powerbi_dataset_predicciones.csv
```

Si Power BI abre con datos anteriores, presiona **Actualizar** dentro de Power BI Desktop.

## Google Colab

El código base está en:

```text
scripts_colab/smartcampus_colab_api.py
```

Debes ejecutar Colab y pegar la URL de ngrok en:

```text
backend/app/config/ExternalServices.php
```

Ejemplo:

```php
'COLAB_API_URL' => 'https://tu-url-ngrok.ngrok-free.dev',
```

>>>>>>> 30aeb43 (Actualización V10: Smart Campus con Power BI y Google Colab integrados)
