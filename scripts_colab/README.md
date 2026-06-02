# Google Colab

1. Abre Google Colab.
2. Copia el contenido de `smartcampus_colab_api.py`.
3. Ejecuta primero la instalación:

```python
!pip -q install flask flask-cors pyngrok pandas scikit-learn joblib
```

4. Ejecuta el código de Flask.
5. Ejecuta la celda de ngrok con tu token.
6. Copia la URL pública y pégala en:

```text
backend/app/config/ExternalServices.php
```

Ejemplo:

```php
'COLAB_API_URL' => 'https://tu-url.ngrok-free.dev',
```
