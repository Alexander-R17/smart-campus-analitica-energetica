# Base de datos Supabase

Usa este archivo principal:

```text
01_supabase_sql_editor_pegar_aqui.sql
```

Ese script se pega directamente en:

```text
Supabase → SQL Editor → New query → Run
```

Crea las mismas 6 tablas de tu modelo MySQL/phpMyAdmin:

- dimambiente
- dimedificio
- dimocupacion
- dimtiempo
- factconsumoenergetico
- fact_consumo_energetico_pred

También crea dos tablas técnicas de staging para conservar el flujo 1 al 7 de la página:

- staging_upload_batch
- staging_lectura_cruda

Si quieres solo estructura sin datos, usa:

```text
supabase_schema_solo_estructura.sql
```
