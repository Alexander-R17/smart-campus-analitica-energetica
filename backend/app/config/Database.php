<?php

/**
 * Compatibilidad histórica.
 * En esta versión ya no se abre conexión MySQL/PDO local.
 * La persistencia se realiza con SupabaseRestClient contra Supabase PostgreSQL cloud.
 */
class Database
{
    public static function supabase(): SupabaseRestClient
    {
        return new SupabaseRestClient();
    }

    public static function connect(): PDO
    {
        throw new RuntimeException('MySQL local fue reemplazado por Supabase. Usa Database::supabase().');
    }
}
