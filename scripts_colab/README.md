# Google Colab — Procesos 5 y 6 Cloud

Usa preferentemente:

```text
PROCESO_5_Y_6_CLOUD.ipynb
```

También se deja el script equivalente:

```text
smartcampus_colab_api_cloud.py
```

## Endpoints

- `/health`: prueba de API activa.
- `/predict`: recibe CSV exportado desde el Data Warehouse cloud por el backend PHP.
- `/predict-cloud-db`: permite que Colab consulte directamente la base de datos cloud si tu proveedor permite conexiones externas.

## Modo recomendado

En `backend/app/config/ExternalServices.php` deja:

```php
'COLAB_INPUT_MODE' => 'backend_export'
```

Este modo evita problemas de IP dinámica de Colab. Técnicamente los datos siguen saliendo del Data Warehouse cloud, solo que el backend exporta el dataset y lo entrega al motor IA.
