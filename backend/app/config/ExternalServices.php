<?php

return [
    // Pega aquí la URL base que te da ngrok, SIN /predict.
    'COLAB_API_URL' => 'https://cupbearer-backed-surgery.ngrok-free.dev',

    // Rutas internas del proyecto.
    'UPLOAD_CSV_PATH' => __DIR__ . '/../../../storage/uploads/ultimo_dataset.csv',
    'PROCESSED_CSV_PATH' => __DIR__ . '/../../../powerbi/data/smartcampus_powerbi_dataset_predicciones.csv',

    // Coloca aquí tu archivo .pbix real cuando lo tengas listo.
    'POWERBI_REPORT_PATH' => __DIR__ . '/../../../powerbi/report/smartcampus_reporte.pbix',

    // MySQL XAMPP. Cambia 3309 a 3306 si tu XAMPP usa el puerto normal.
    'MYSQL_HOST' => '127.0.0.1',
    'MYSQL_PORT' => '3309',
    'MYSQL_DB' => 'smartcampus',
    'MYSQL_USER' => 'root',
    'MYSQL_PASS' => '',

    // Si Colab falla, false muestra error. true crea una salida básica local.
    'LOCAL_FALLBACK' => false
];
