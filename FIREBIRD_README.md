# Firebird PDO Driver for Doctrine DBAL

Bu paket, Doctrine DBAL için Firebird SQL veritabanı desteği sağlar.

## Kurulum

```bash
composer require foodsoft/doctrine-firebird-driver
```

## Gereksinimler

- PHP ^8.1
- Doctrine DBAL ^4.0
- ext-pdo
- ext-pdo_firebird

## Kullanım

### Temel Bağlantı

```php
<?php

require_once 'vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Foodsoft\FirebirdDriver\FirebirdDriver;

$connectionParams = [
    'driverClass' => FirebirdDriver::class,
    'host'        => 'localhost',
    'port'        => 3050,
    'dbname'      => '/path/to/database.fdb',
    'user'        => 'SYSDBA',
    'password'    => 'masterkey',
    'charset'     => 'UTF8',
    'dialect'     => 3,
];

$conn = DriverManager::getConnection($connectionParams);

// Sorgu çalıştırma
$result = $conn->executeQuery('SELECT * FROM MY_TABLE');
$rows = $result->fetchAllAssociative();
```

### DSN Formatı

Driver, aşağıdaki DSN formatlarını destekler:

#### Uzak Bağlantı (TCP/IP)
```
firebird:dbname=hostname/port:/path/to/database.fdb
```

#### Yerel Bağlantı
```
firebird:dbname=/path/to/database.fdb
```

### Bağlantı Parametreleri

- `host`: Veritabanı sunucu adresi (varsayılan: localhost)
- `port`: Bağlantı portu (varsayılan: 3050)
- `dbname`: Veritabanı dosya yolu (zorunlu)
- `user`: Kullanıcı adı
- `password`: Şifre
- `charset`: Karakter seti (örn: UTF8, WIN1254, ISO8859_9)
- `dialect`: SQL lehçesi (varsayılan: 3)
- `role`: Firebird role adı

## Özellikler

- ✅ PDO tabanlı Firebird bağlantısı
- ✅ Sequence (Generator) desteği
- ✅ Transaction isolation level desteği
- ✅ BLOB (BINARY ve TEXT) desteği
- ✅ Schema yönetimi
- ✅ Tam Doctrine DBAL entegrasyonu
- ✅ Exception mapping

### Desteklenen Transaction Isolation Levels

```php
use Doctrine\DBAL\TransactionIsolationLevel;

// READ COMMITTED
$conn->setTransactionIsolation(TransactionIsolationLevel::READ_COMMITTED);

// SNAPSHOT (REPEATABLE READ)
$conn->setTransactionIsolation(TransactionIsolationLevel::REPEATABLE_READ);

// SNAPSHOT TABLE STABILITY (SERIALIZABLE)
$conn->setTransactionIsolation(TransactionIsolationLevel::SERIALIZABLE);
```

### Sequence (Generator) İşlemleri

```php
// Sequence oluşturma
$platform = $conn->getDatabasePlatform();
$sql = $platform->getCreateSequenceSQL('my_sequence', 1, 1);
$conn->executeStatement($sql);

// Sequence'dan değer alma
$nextId = $conn->fetchOne("SELECT GEN_ID(my_sequence, 1) FROM RDB\$DATABASE");
```

## Platform-Specific SQL

### BLOB Kolonları

```php
// TEXT BLOB
$table->addColumn('description', 'text');

// BINARY BLOB
$table->addColumn('file_content', 'blob');
```

### Date Arithmetic

```php
// Tarih ekleme/çıkarma
$platform->getDateArithmeticIntervalExpression(
    'created_at',
    '+',
    7,
    'DAY'
); // DATEADD(7 DAY TO created_at)
```

## Örnek Proje

`samples/fb-connect.php` dosyasında basit bir örnek bulabilirsiniz:

```bash
php samples/fb-connect.php
```

## Hata Yönetimi

Driver, Firebird hatalarını otomatik olarak Doctrine exception'larına dönüştürür:

- Foreign key ihlalleri → `ForeignKeyConstraintViolationException`
- Unique constraint ihlalleri → `UniqueConstraintViolationException`
- Not null constraint ihlalleri → `NotNullConstraintViolationException`
- Tablo bulunamadı → `TableNotFoundException`
- Syntax hataları → `SyntaxErrorException`

## Lisans

MIT License

## Katkıda Bulunma

Pull request'ler memnuniyetle karşılanır. Büyük değişiklikler için lütfen önce bir issue açın.

## Referanslar

- [Doctrine DBAL Documentation](https://www.doctrine-project.org/projects/dbal.html)
- [Firebird SQL Documentation](https://firebirdsql.org/en/documentation/)
- [satwareAG/doctrine-firebird-driver](https://github.com/satwareAG/doctrine-firebird-driver) - Bu proje için ilham kaynağı
