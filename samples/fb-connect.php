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
    $result = $conn->executeQuery('SELECT FIRST 5 * FROM RDB$RELATIONS');
    $tables = $result->fetchAllAssociative();
    
    echo "Bağlantı başarılı!\n\n";
    echo "İlk 5 sistem tablosu:\n";
    print_r($tables);
    
} catch (\Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n\n";
    echo "Hata kodu: " . $e->getCode() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
}

echo '</pre>';
