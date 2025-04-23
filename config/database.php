<?php
class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            $host = '192.168.1.55';
            $dbname = 'web2';
            $user = 'postgres';
            $pass = 'dsds';

            try {
                self::$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new Exception("Erro de conexão: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}