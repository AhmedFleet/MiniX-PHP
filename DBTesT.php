<?php
$conn = pg_connect("host=aws-0-eu-west-3.pooler.supabase.com port=5432 dbname=postgres user=postgres.lwtuacjrlncoljyncdje password=MiniX2001 sslmode=require");

if (!$conn) {
    echo "                                "."                                                    ❌ Connection failed.";
} else {
    echo "                                                    ✅ Connected to Supabase PostgreSQL!";
}
?>
