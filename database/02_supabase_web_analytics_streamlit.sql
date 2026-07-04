-- Ejecutar en Supabase SQL Editor.
-- Agrega analítica de comportamiento del usuario para la página 3 del dashboard Streamlit.

create table if not exists web_eventos (
    id_evento bigserial primary key,
    fecha_hora timestamptz default now(),
    fecha date default current_date,
    usuario_id text,
    sesion_id text,
    evento text not null,
    etapa_numero int,
    etapa_nombre text,
    dispositivo text,
    navegador text,
    resultado text,
    tiempo_seg int,
    url_pagina text
);

create index if not exists idx_web_eventos_fecha on web_eventos(fecha);
create index if not exists idx_web_eventos_sesion on web_eventos(sesion_id);
create index if not exists idx_web_eventos_evento on web_eventos(evento);
create index if not exists idx_web_eventos_etapa on web_eventos(etapa_numero);

-- Datos demo opcionales para que la página 3 se vea antes de tener tráfico real.
insert into web_eventos (fecha_hora, fecha, usuario_id, sesion_id, evento, etapa_numero, etapa_nombre, dispositivo, navegador, resultado, tiempo_seg, url_pagina)
select * from (values
(now() - interval '80 minutes', current_date, 'Ingeniero1', 'S001', 'login_usuario', 0, 'Login', 'Desktop', 'Chrome', 'exitoso', 0, '/'),
(now() - interval '78 minutes', current_date, 'Ingeniero1', 'S001', 'carga_archivo', 1, 'Fuentes de datos', 'Desktop', 'Chrome', 'exitoso', 90, '/'),
(now() - interval '76 minutes', current_date, 'Ingeniero1', 'S001', 'validacion_staging', 2, 'Staging Area', 'Desktop', 'Chrome', 'exitoso', 160, '/'),
(now() - interval '74 minutes', current_date, 'Ingeniero1', 'S001', 'etl_completado', 3, 'Proceso ETL', 'Desktop', 'Chrome', 'exitoso', 250, '/'),
(now() - interval '72 minutes', current_date, 'Ingeniero1', 'S001', 'modelo_copo_nieve_completado', 4, 'Data Warehouse', 'Desktop', 'Chrome', 'exitoso', 330, '/'),
(now() - interval '70 minutes', current_date, 'Ingeniero1', 'S001', 'colab_ia_completado', 5, 'Capa IA', 'Desktop', 'Chrome', 'exitoso', 430, '/'),
(now() - interval '68 minutes', current_date, 'Ingeniero1', 'S001', 'visualizacion_dashboard', 7, 'Dashboard Streamlit', 'Desktop', 'Chrome', 'completado', 520, '/'),
(now() - interval '45 minutes', current_date, 'Usuario2', 'S002', 'login_usuario', 0, 'Login', 'Mobile', 'Chrome', 'exitoso', 0, '/'),
(now() - interval '43 minutes', current_date, 'Usuario2', 'S002', 'carga_archivo', 1, 'Fuentes de datos', 'Mobile', 'Chrome', 'exitoso', 80, '/'),
(now() - interval '42 minutes', current_date, 'Usuario2', 'S002', 'abandono_flujo', 2, 'Staging Area', 'Mobile', 'Chrome', 'incompleto', 125, '/'),
(now() - interval '30 minutes', current_date, 'Usuario3', 'S003', 'login_usuario', 0, 'Login', 'Desktop', 'Edge', 'exitoso', 0, '/'),
(now() - interval '27 minutes', current_date, 'Usuario3', 'S003', 'carga_archivo', 1, 'Fuentes de datos', 'Desktop', 'Edge', 'exitoso', 120, '/'),
(now() - interval '25 minutes', current_date, 'Usuario3', 'S003', 'validacion_staging', 2, 'Staging Area', 'Desktop', 'Edge', 'exitoso', 180, '/'),
(now() - interval '24 minutes', current_date, 'Usuario3', 'S003', 'error_proceso', 3, 'Proceso ETL', 'Desktop', 'Edge', 'error', 220, '/')
) as v(fecha_hora, fecha, usuario_id, sesion_id, evento, etapa_numero, etapa_nombre, dispositivo, navegador, resultado, tiempo_seg, url_pagina)
where not exists (select 1 from web_eventos limit 1);
