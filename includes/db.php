<?php
// includes/db.php

class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct()
    {
        // Vercel veya diğer ortamlardan gelen değişkenleri oku, yoksa yerel varsayılanları kullan
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'php_blog';
        $this->username = getenv('DB_USER') ?: 'blog_user';
        $this->password = getenv('DB_PASS') ?: 'blog_pass';
        $this->port = getenv('DB_PORT') ?: '5432';
    }

    public function getConnection()
    {
        $this->conn = null;
        $db_type = getenv('DB_TYPE') ?: 'mysql'; // Varsayılan yerel mysql

        try {
            if ($db_type === 'pgsql') {
                // Supabase/PostgreSQL Bağlantısı
                $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            } else {
                // Yerel MySQL Bağlantısı
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            }

            $this->conn = new PDO($dsn, $this->username, $this->password);

            // PostgreSQL için UTF8 ayarı
            if ($db_type === 'pgsql') {
                $this->conn->exec("SET names 'utf8'");
            }

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            echo "Veritabanı bağlantı hatası Detay: " . $exception->getMessage() . "<br>Host: " . $this->host . "<br>Port: " . $this->port . "<br>DB: " . $this->db_name . "<br>User: " . $this->username . "<br>Type: " . $db_type;
        }

        return $this->conn;
    }
}


