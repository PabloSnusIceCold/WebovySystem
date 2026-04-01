# Rýchly štart pre spustenie testov

## Príprava

### 1. Skontroluj .env.testing
Skontroluj, že máš `.env.testing` súbor s nasledujúcou konfiguráciou:

```env
APP_NAME=WebovySystem
APP_ENV=testing
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
```

### 2. Nainštaluj závislosti
```bash
composer install
```

### 3. Generuj APP_KEY
```bash
php artisan key:generate --env=testing
```

## Spustenie testov

### Všetky testy naraz
```bash
php artisan test
```

### Iba Feature testy (interakcia s aplikáciou)
```bash
php artisan test tests/Feature
```

### Iba Unit testy (modely a vzťahy)
```bash
php artisan test tests/Unit
```

### Konkrétny test súbor
```bash
php artisan test tests/Feature/AuthenticationTest.php
php artisan test tests/Feature/DatasetCRUDTest.php
php artisan test tests/Feature/AdminCategoryTest.php
```

### Konkrétny test
```bash
php artisan test tests/Feature/AuthenticationTest.php --filter=test_user_can_register_with_valid_credentials
```

### S detailným výstupom
```bash
php artisan test --verbose
```

### S pokrytím kódu (XML report)
```bash
php artisan test --coverage
```

## Zoznam všetkých testov podľa súborov

### Feature testy (11 súborov, ~160 testov)

1. **AuthenticationTest.php** - Autentifikácia a autorizácia
   ```bash
   php artisan test tests/Feature/AuthenticationTest.php
   ```
   - Registrácia, prihlásenie, odhlásenie
   - Validácia hesla
   - Ochrana trás

2. **DatasetCRUDTest.php** - CRUD operácie s datasetmi
   ```bash
   php artisan test tests/Feature/DatasetCRUDTest.php
   ```
   - Upload (1 alebo viacero súborov)
   - Čítanie (zobrazenie, viditeľnosť)
   - Úprava (názov, popis)
   - Zmazanie (so súbormi)
   - Vyhľadávanie a filtrovanie

3. **RepositoryTest.php** - Správa repozitárov
   ```bash
   php artisan test tests/Feature/RepositoryTest.php
   ```
   - Vytvorenie s priradenými datasetmi
   - Zdieľanie s share tokenom
   - Verejné prezeranie

4. **AdminCategoryTest.php** - Správa kategórií
   ```bash
   php artisan test tests/Feature/AdminCategoryTest.php
   ```
   - CRUD kategórií
   - Kaskádové zmazanie

5. **AdminUserTest.php** - Správa používateľov
   ```bash
   php artisan test tests/Feature/AdminUserTest.php
   ```
   - CRUD používateľov
   - Roly (user ↔ admin)
   - Zmazanie s datasetmi

6. **AdminDatasetTest.php** - Admin panel pre datasety
   ```bash
   php artisan test tests/Feature/AdminDatasetTest.php
   ```
   - Úprava všetkých datasetov
   - Zmena viditeľnosti
   - Zmena kategórie

7. **HomePageTest.php** - Domáca stránka
   ```bash
   php artisan test tests/Feature/HomePageTest.php
   ```
   - Viditeľnosť podle roly
   - Filtrovanie a vyhľadávanie
   - Top listy

8. **DatasetSharingTest.php** - Zdieľanie datasetov
   ```bash
   php artisan test tests/Feature/DatasetSharingTest.php
   ```
   - Share tokeny
   - Verejný prístup
   - Stiahnutie cez share

9. **DatasetFilesAjaxTest.php** - AJAX správa súborov
   ```bash
   php artisan test tests/Feature/DatasetFilesAjaxTest.php
   ```
   - Pridávanie súborov
   - Zmazanie súborov
   - AJAX update/delete

10. **DatasetDownloadTest.php** - Stiahnutie datasetov
    ```bash
    php artisan test tests/Feature/DatasetDownloadTest.php
    ```
    - Kontrola oprávnení
    - ZIP formát
    - Inkrementácia counter

11. **DatasetDownloadCountAndLikesTest.php** - Počítadlá a Like
    ```bash
    php artisan test tests/Feature/DatasetDownloadCountAndLikesTest.php
    ```
    - Download count
    - Like systém
    - Toggle like

### Unit testy (1 súbor, ~17 testov)

1. **ModelRelationshipsTest.php** - Vzťahy medzi modelmi
   ```bash
   php artisan test tests/Unit/ModelRelationshipsTest.php
   ```
   - User ↔ Dataset
   - Dataset ↔ Files
   - Dataset ↔ Category
   - Repository ↔ Dataset
   - Computed attributes

## Očakávané výstupy

### Úspešný test
```
PASS tests/Feature/AuthenticationTest.php (12 tests) 
PASS tests/Feature/DatasetCRUDTest.php (20 tests)
...

Tests:  179 passed (xxx assertions)
Time:   45s
```

### Zlyhaný test
```
FAIL tests/Feature/AuthenticationTest.php
  test_user_can_register_with_valid_credentials
  AssertionError: Failed asserting that array has key 'email'.
```

## Riešenie problémov

### Test nebeží, chyba "Database not found"
```bash
# Skontroluj .env.testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# A spusti:
php artisan migrate --env=testing
```

### Test nechce spustiť Storage operácie
```bash
# Skontroluj, že test používa:
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class MyTest extends TestCase {
    use RefreshDatabase;
    
    public function test_something(): void {
        Storage::fake('local');
        // ... test code
    }
}
```

### AJAX test zlyhá
```bash
# Skontroluj header v teste:
->withHeader('X-Requested-With', 'XMLHttpRequest')
```

### Test je pomalý
```bash
# Spusti iba Feature alebo Unit testy:
php artisan test tests/Feature
# alebo
php artisan test tests/Unit
```

### Potrebujem vidieť Sql querys
```bash
# Pridaj do testa:
DB::listen(function ($query) {
    dump($query->sql);
});
```

## Skriptovanie

### Spustenie všetkých testov s reportom
```bash
#!/bin/bash
echo "=== Running all tests ==="
php artisan test --coverage
echo "=== Tests completed ==="
```

### Spustenie iba zlyhaných testov
```bash
php artisan test --fails
```

### Watch mode (spusti testy automaticky pri zmene)
```bash
php artisan test --watch
```

## Profiling testov

### Zbytok času podľa testu
```bash
php artisan test --profile
```

Výstup:
```
Top 5 slowest tests:
  test_something .................. 1.234s
  test_something_else ............. 0.987s
  ...
```

## CI/CD Integration

Ak používaš GitHub Actions, GitLab CI, alebo Travis:

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
      
      - run: composer install
      - run: php artisan test
```

## Bezpečnostné kontroly

Pred produkčným nasadením spusti:

```bash
# 1. Všetky testy musia prejsť
php artisan test

# 2. Žiadne security issues
composer audit

# 3. Code style
./vendor/bin/pint --test

# 4. Static analysis
./vendor/bin/phpstan analyse

# 5. Migrate production
php artisan migrate --force
```

## Notes

- Testy používajú in-memory SQLite, NIE MySQL z dockeru
- Všetky súbory sú fake (Storage::fake)
- Datasety majú izolovaný stav pre každý test (RefreshDatabase)
- Transakcie v testoch sa rollback po teste
- Malý dataset (~179 testov) by mal prejsť v < 1 minúte

---

**Happy testing! 🚀**

