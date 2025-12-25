# Symfony Framework Entegrasyonu

Bu döküman, Firebird Driver'ı Symfony projelerinize nasıl entegre edeceğinizi gösterir.

## 1. Yerel Paket Olarak Kurulum

### composer.json'unuza yerel repository ekleyin:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../doctrine-firebird-driver",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "foodsoft/doctrine-firebird-driver": "@dev"
    }
}
```

### Paketi yükleyin:

```bash
composer require foodsoft/doctrine-firebird-driver:@dev
```

## 2. Bundle'ı Etkinleştirin

`config/bundles.php` dosyasına ekleyin:

```php
<?php

return [
    // ... diğer bundle'lar
    Foodsoft\FirebirdDriver\FirebirdDriverBundle::class => ['all' => true],
];
```

## 3. Doctrine DBAL Konfigürasyonu

### config/packages/doctrine.yaml

```yaml
doctrine:
    dbal:
        connections:
            default:
                driver_class: Foodsoft\FirebirdDriver\FirebirdDriver
                host: localhost
                port: 3050
                dbname: 'c:/path/to/database.fdb'
                user: SYSDBA
                password: masterkey
                charset: UTF8
                options:
                    dialect: 3
                    
            # İkinci bir Firebird bağlantısı (opsiyonel)
            firebird_secondary:
                driver_class: Foodsoft\FirebirdDriver\FirebirdDriver
                host: remote-server
                port: 3050
                dbname: '/var/lib/firebird/data/secondary.fdb'
                user: SYSDBA
                password: '%env(FIREBIRD_PASSWORD)%'
                charset: UTF8
```

### .env dosyanıza ekleyin:

```env
###> doctrine/dbal ###
FIREBIRD_HOST=localhost
FIREBIRD_PORT=3050
FIREBIRD_DATABASE=c:/path/to/database.fdb
FIREBIRD_USER=SYSDBA
FIREBIRD_PASSWORD=masterkey
FIREBIRD_CHARSET=UTF8
###< doctrine/dbal ###
```

### Alternatif: Environment variables ile:

```yaml
doctrine:
    dbal:
        driver_class: Foodsoft\FirebirdDriver\FirebirdDriver
        host: '%env(FIREBIRD_HOST)%'
        port: '%env(int:FIREBIRD_PORT)%'
        dbname: '%env(FIREBIRD_DATABASE)%'
        user: '%env(FIREBIRD_USER)%'
        password: '%env(FIREBIRD_PASSWORD)%'
        charset: '%env(FIREBIRD_CHARSET)%'
        options:
            dialect: 3
```

## 4. Symfony Uygulamanızda Kullanım

### Controller'da kullanım:

```php
<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FirebirdController extends AbstractController
{
    #[Route('/firebird/test', name: 'firebird_test')]
    public function test(Connection $connection): Response
    {
        // Sorgu çalıştır
        $result = $connection->executeQuery('SELECT FIRST 10 * FROM RDB$RELATIONS');
        $tables = $result->fetchAllAssociative();
        
        return $this->json([
            'status' => 'success',
            'tables' => $tables
        ]);
    }
    
    #[Route('/firebird/sequences', name: 'firebird_sequences')]
    public function sequences(Connection $connection): Response
    {
        // Sequence oluştur
        $platform = $connection->getDatabasePlatform();
        $sql = $platform->getCreateSequenceSQL('my_sequence', 1, 1);
        $connection->executeStatement($sql);
        
        // Sequence'dan değer al
        $nextId = $connection->fetchOne("SELECT GEN_ID(MY_SEQUENCE, 1) FROM RDB\$DATABASE");
        
        return $this->json([
            'next_id' => $nextId
        ]);
    }
}
```

### Service'te kullanım:

```php
<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class FirebirdDataService
{
    public function __construct(
        private readonly Connection $connection
    ) {}
    
    public function fetchCustomerData(int $customerId): array
    {
        return $this->connection->fetchAssociative(
            'SELECT * FROM CUSTOMERS WHERE ID = ?',
            [$customerId]
        );
    }
    
    public function createCustomer(array $data): int
    {
        $platform = $this->connection->getDatabasePlatform();
        
        // Sequence'dan yeni ID al
        $newId = $this->connection->fetchOne(
            "SELECT GEN_ID(CUSTOMER_ID_SEQ, 1) FROM RDB\$DATABASE"
        );
        
        $this->connection->insert('CUSTOMERS', [
            'ID' => $newId,
            'NAME' => $data['name'],
            'EMAIL' => $data['email']
        ]);
        
        return $newId;
    }
}
```

### Repository Pattern ile kullanım:

```php
<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class CustomerRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {}
    
    public function findAll(): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT * FROM CUSTOMERS ORDER BY NAME'
        );
    }
    
    public function findById(int $id): ?array
    {
        $result = $this->connection->fetchAssociative(
            'SELECT * FROM CUSTOMERS WHERE ID = ?',
            [$id]
        );
        
        return $result ?: null;
    }
    
    public function search(string $term): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT * FROM CUSTOMERS WHERE NAME CONTAINING ? ORDER BY NAME',
            [$term]
        );
    }
}
```

## 5. Çoklu Firebird Bağlantısı

Birden fazla Firebird veritabanı kullanıyorsanız:

```yaml
doctrine:
    dbal:
        default_connection: main
        connections:
            main:
                driver_class: Foodsoft\FirebirdDriver\FirebirdDriver
                host: localhost
                dbname: 'c:/data/main.fdb'
                user: SYSDBA
                password: masterkey
                
            archive:
                driver_class: Foodsoft\FirebirdDriver\FirebirdDriver
                host: localhost
                dbname: 'c:/data/archive.fdb'
                user: SYSDBA
                password: masterkey
```

Service'te kullanım:

```php
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

class MultiDatabaseService
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {}
    
    public function getMainConnection(): Connection
    {
        return $this->registry->getConnection('main');
    }
    
    public function getArchiveConnection(): Connection
    {
        return $this->registry->getConnection('archive');
    }
}
```

## 6. Transaction Yönetimi

```php
public function transferData(): void
{
    $this->connection->beginTransaction();
    
    try {
        // İşlemler
        $this->connection->insert('TABLE1', $data1);
        $this->connection->insert('TABLE2', $data2);
        
        $this->connection->commit();
    } catch (\Exception $e) {
        $this->connection->rollBack();
        throw $e;
    }
}
```

## 7. Firebird-Specific Özellikler

### BLOB Yönetimi:

```php
// TEXT BLOB yazma
$this->connection->insert('DOCUMENTS', [
    'ID' => 1,
    'CONTENT' => $largeText,  // Otomatik BLOB'a dönüştürülür
]);

// BLOB okuma
$content = $this->connection->fetchOne(
    'SELECT CONTENT FROM DOCUMENTS WHERE ID = ?',
    [1]
);
```

### Sequence/Generator kullanımı:

```php
// Yeni sequence oluştur
$platform = $this->connection->getDatabasePlatform();
$sql = $platform->getCreateSequenceSQL('MY_SEQ', 1, 1);
$this->connection->executeStatement($sql);

// Değer al
$nextVal = $this->connection->fetchOne(
    "SELECT GEN_ID(MY_SEQ, 1) FROM RDB\$DATABASE"
);
```

### Transaction Isolation Levels:

```php
use Doctrine\DBAL\TransactionIsolationLevel;

// SNAPSHOT (Repeatable Read)
$this->connection->setTransactionIsolation(
    TransactionIsolationLevel::REPEATABLE_READ
);

// READ COMMITTED
$this->connection->setTransactionIsolation(
    TransactionIsolationLevel::READ_COMMITTED
);
```

## 8. Debugging ve Logging

SQL sorgularını görmek için:

```yaml
# config/packages/dev/doctrine.yaml
doctrine:
    dbal:
        logging: true
        profiling: true
```

## 9. Testing

Test ortamında kullanım:

```yaml
# config/packages/test/doctrine.yaml
doctrine:
    dbal:
        driver_class: Foodsoft\FirebirdDriver\FirebirdDriver
        host: localhost
        dbname: 'c:/data/test.fdb'
        user: SYSDBA
        password: masterkey
```

Functional test örneği:

```php
<?php

namespace App\Tests\Service;

use App\Service\FirebirdDataService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FirebirdDataServiceTest extends KernelTestCase
{
    private FirebirdDataService $service;
    
    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(FirebirdDataService::class);
    }
    
    public function testFetchCustomerData(): void
    {
        $data = $this->service->fetchCustomerData(1);
        $this->assertIsArray($data);
    }
}
```

## 10. Production Deployment

Production ortamında:

```yaml
# config/packages/prod/doctrine.yaml
doctrine:
    dbal:
        driver_class: Foodsoft\FirebirdDriver\FirebirdDriver
        # Connection pooling için
        options:
            persistent: true
            dialect: 3
```

## Sorun Giderme

### PDO Extension eksik hatası:

```bash
# Windows
# php.ini dosyasında aktifleştirin:
extension=pdo_firebird

# Linux
sudo apt-get install php-interbase
```

### Karakter seti sorunları:

```yaml
doctrine:
    dbal:
        charset: UTF8  # veya WIN1254, ISO8859_9
```

### Bağlantı timeout:

```yaml
doctrine:
    dbal:
        options:
            timeout: 30
```

## Notlar

- Firebird 2.5+ desteklenir
- PHP 8.1+ gereklidir
- Symfony 6.0+ veya 7.0+ desteklenir
- Doctrine DBAL 4.0+ gereklidir

## Daha Fazla Bilgi

- [Firebird SQL Dokümantasyonu](https://firebirdsql.org/en/documentation/)
- [Doctrine DBAL Dokümantasyonu](https://www.doctrine-project.org/projects/dbal.html)
- [Symfony Dokümantasyonu](https://symfony.com/doc/current/index.html)
