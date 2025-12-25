<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Foodsoft\FirebirdDriver\FirebirdDriver;

$connectionParams = [
    'driverClass' => FirebirdDriver::class,
    'host'        => 'localhost',
    'port'        => 3050,
    'dbname'      => 'c:/tekkalem/kobisoft/data/mer2019/data.fdb',
    'user'        => 'SYSDBA',
    'password'    => 'masterkey',
    'charset'     => 'UTF8',
    'dialect'     => 3,
];

$conn = DriverManager::getConnection($connectionParams);

echo '<pre>';

try {
    // Test sorgusu - otomatik bağlantı yapacaktır
    // $result = $conn->executeQuery('SELECT FIRST 5 * FROM RDB$RELATIONS');
    // $tables = $result->fetchAllAssociative();

    // echo "Bağlantı başarılı!\n\n";
    // echo "İlk 5 sistem tablosu:\n";
    $result = $conn->executeQuery('DELETE FROM MOBIL_KULLANICI WHERE ID > 250');
    $id = $nextId = $conn->fetchOne("SELECT GEN_ID(GEN_MOBIL_KULLANICI_ID, 1) FROM RDB\$DATABASE");
    $result = $conn->executeQuery("INSERT INTO MOBIL_KULLANICI (ID, KULLANICI_ADI, MODIFIED_AT) VALUES ($id, 'TEST USER', CURRENT_TIMESTAMP)");
    $result = $conn->executeQuery('SELECT first 5 * FROM MOBIL_KULLANICI ORDER BY ID DESC');
    $tables = $result->fetchAllAssociative();

    print_r($tables);
} catch (\Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n\n";
    echo "Hata kodu: " . $e->getCode() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
}

echo '</pre>';
