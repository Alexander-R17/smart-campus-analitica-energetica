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

```text
Usuario: Ingeniero1
Contraseña: 12345678
```

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
