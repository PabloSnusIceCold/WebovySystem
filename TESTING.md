# Komplexný Test Suite pre WebovýSystem

Tento dokument popisuje všetky testy implementované pre projekt WebovýSystem. Test suite obsahuje viac ako **100 testovacích prípadov** pokrývajúcich všetky kritické časti aplikácie.

## Štruktúra testov

Testy sú organizované do nasledovných kategórií:

### Feature Testy (v adresári `tests/Feature/`)

#### 1. **AuthenticationTest.php** (10 testov)
Testuje autentifikáciu a autorizáciu:
- Registrácia s platným údajmi
- Validácia hesla (vyžaduje veľké a malé písmená, čísla, minimálne 8 znakov)
- Chyby pri duplikátnom mene používateľa/emailu
- Prihlásenie s platnými a neplatnými povereniami
- Odhlásenie
- Ochrana chránených trás

**Kľúčové oveľa**: Heslo musia splňovať požiadavky, meno a email sú unikátne

#### 2. **DatasetCRUDTest.php** (25 testov)
Testuje CRUD operácie s datasetmi:

**CREATE:**
- Upload datasetu s jedným a viacerými súbormi
- Automatická detekcia typu súboru (CSV, TXT, XLSX, JSON, XML, ARFF, ZIP)
- Uloženie do storage a vytvorenie záznamov v DB
- Validácia povinných polí (názov, kategória, aspoň jeden súbor)
- Podpora verejných a súkromných datasetov
- Chyby pri nepodporovaných typoch súborov

**READ:**
- Zobrazenie vlastného datasetu
- Zobrazenie verejného datasetu kýmkoľvek
- Zákaz prístupu k cudzím súkromným datasetom
- Admin má prístup ku všetkým datasetom

**UPDATE:**
- Úprava názvu a popisu datasetu
- Iba majiteľ alebo admin môžu upravovať

**DELETE:**
- Zmazanie datasetu
- Zmazanie fyzických súborov z storage
- Zmazanie záznamov v DB

**SEARCH & FILTER:**
- Vyhľadávanie podľa názvu
- Vyhľadávanie podľa popisu
- Podpora kombinácie filtrov

**Kľúčové detaily**: Súbory sú uložené v storage/datasets a ich cesty sú evidované v DB tabuľke `files`

#### 3. **RepositoryTest.php** (13 testov)
Testuje správu repozitárov (zbierky datasetov):
- Vytvorenie repozitára s priradenými datasetmi
- Validácia - názov je povinný
- Zobrazenie detailov repozitára
- Ochrana prístupu - iba majiteľ alebo admin
- Zdieľanie repozitára - generovanie share tokenu (UUID)
- Verejné prezeranie zdieľaného repozitára
- Vyhľadávanie repozitárov
- Idempotentnosť share tokenu

**Kľúčové detaily**: Repozitár je zbierka datasetov s możnosťou zdieľania

#### 4. **AdminCategoryTest.php** (13 testov)
Testuje správu kategórií (iba admin):
- Zobrazenie zoznamu kategórií s počtom datasetov
- Vytvorenie novej kategórie
- Úprava kategórie
- Zmazanie kategórie (a všetkých jej datasetov)
- Validácia - názov je povinný a unikátny
- Ochrana pred duplikátnymi názvami

**Kľúčové detaily**: Zmazanie kategórie kaskádovite zmaže všetky datasety a ich súbory

#### 5. **AdminUserTest.php** (13 testov)
Testuje správu používateľov (iba admin):
- Zobrazenie zoznamu používateľov s počtom datasetov
- Vytvorenie nového používateľa s rolou
- Úprava údajov používateľa
- Zmena hesla používateľa
- Zmena roly (user ↔ admin)
- Zmazanie používateľa
- Automatické odhlásenie pri vlastnom zmazaní
- Validácia hesla (rovnaké požiadavky ako pri registrácii)

**Kľúčové detaily**: Zmazanie admin-a sa odsúva a je odhlásený z aplikácie

#### 6. **AdminDatasetTest.php** (10 testov)
Testuje správu datasetov v admin paneli:
- Zobrazenie všetkých datasetov (verejných aj súkromných)
- Úprava datasetu (názov, popis, kategória, viditeľnosť)
- Zmazanie datasetu s fyzickými súbormi
- Zmena kategórie datasetu
- Zmena viditeľnosti (verejný/súkromný)
- Paginalizácia zoznamu

**Kľúčové detaily**: Admin vidí všetky datasety nezávisle od viditeľnosti

#### 7. **HomePageTest.php** (13 testov)
Testuje domácu stránku:
- Host vidí iba verejné datasety
- Prihlásený používateľ vidí verejné + svoje datasety
- Admin vidí všetky datasety
- Filtrovanie podľa kategórie
- Vyhľadávanie po názvu a popise
- Reset filtrov
- Zobrazenie top 5 datasetov podľa downloadov
- Zobrazenie top 5 datasetov podľa úľavov
- Kombinácia vyhľadávania a filtrovania
- Prepínanie medzi card a list layoutom

**Kľúčové detaily**: Domáca stránka rešpektuje oprávnenia viditeľnosti

#### 8. **DatasetSharingTest.php** (8 testov)
Testuje zdieľanie datasetov:
- Generovanie share tokenu (UUID)
- Vrátenie share URL
- Zákaz zdieľania cudzích súkromných datasetov
- Admin môže zdieľať akýkoľvek dataset
- Prezeranie cez share token bez prihlásenia
- Stiahnutie zdieľaného datasetu
- Neplatný token → 404

**Kľúčové detaily**: Share token umožňuje prístup bez prihlásenia k súkromným datasetom

#### 9. **DatasetFilesAjaxTest.php** (12 testov)
Testuje správu súborov cez AJAX:
- Pridávanie súborov k existujúcemu datasetu
- Zmazanie súboru z datasetu
- Fyzické zmazanie súboru zo storage
- Ochrana - iba majiteľ alebo admin
- AJAX update datasetu (názov, popis)
- AJAX zmazanie datasetu
- Validácia - súbory musia existovať

**Kľúčové detaily**: AJAX endpointy vracia JSON odpovede

#### 10. **DatasetDownloadTest.php** (7 testov)
Testuje stiahnutie datasetov:
- Majiteľ môže stiahnuť svoju dataset (aj súkromnu)
- Ľubovoľný používateľ môže stiahnuť verejný dataset
- Zákaz stiahnutia cudzej súkromnej datasety bez share tokenu
- Admin môže stiahnuť ľubovoľný dataset
- Inkrementácia download_count
- Formát ZIP s viacerými súbormi
- AJAX endpoint na inkrementáciu download_count

**Kľúčové detaily**: Datasety sa sťahujú ako ZIP archívy, download_count sa ukladá do DB

#### 11. **DatasetDownloadCountAndLikesTest.php** (11 testov)
Testuje počítadlá a systém „Like":
- Inkrementácia download_count
- Persistencia v DB
- Používateľ môže dať "like" verejnému datasetu
- Používateľ môže odobrať "like"
- Viacerí používatelia môžu dať like na rovnaký dataset
- Toggle like - opakované stlačenie toggle
- Zákaz like na cudzej súkromnej datasety
- Majiteľ môže dať like svojmu datasetu
- Neprihlásený používateľ nemôže dať like
- Viditeľnosť v top 5 podľa počtu downloadov
- Viditeľnosť v top 5 podľa počtu úľavov

**Kľúčové detaily**: Likes sú uložené v pivot tabuľke `dataset_likes`, likes_count je vo `datasets`

### Unit Testy (v adresári `tests/Unit/`)

#### 12. **ModelRelationshipsTest.php** (17 testov)
Testuje vzťahy medzi modelmi:
- User has many Datasets
- Dataset belongs to User
- Dataset belongs to Category
- Category has many Datasets
- Dataset has many Files
- File belongs to Dataset
- User has many Repositories
- Repository belongs to User
- Repository has many Datasets
- Dataset can belong to Repository (nullable)
- Dataset has likes through dataset_likes pivot table
- Computed attributes: total_size, total_size_mb, size_human
- Casts: download_count a likes_count sú integers
- is_public je boolean
- Vzťahy sú správne načítané

**Kľúčové detaily**: Všetky vzťahy medzi Eloquent modelmi

## Spustenie testov

### Spustiť všetky testy:
```bash
php artisan test
```

### Spustiť iba feature testy:
```bash
php artisan test tests/Feature
```

### Spustiť iba unit testy:
```bash
php artisan test tests/Unit
```

### Spustiť konkrétny test súbor:
```bash
php artisan test tests/Feature/AuthenticationTest.php
```

### Spustiť konkrétny test:
```bash
php artisan test tests/Feature/AuthenticationTest.php --filter=test_user_can_register_with_valid_credentials
```

### Spustiť testy s detailným výstupom:
```bash
php artisan test --verbose
```

### Spustiť testy s pokrytím kódu:
```bash
php artisan test --coverage
```

## Pokrytie testu

Test suite pokrýva:

### Autentifikácia a autorizácia (10 testov)
- ✅ Registrácia s validáciou
- ✅ Prihlásenie a odhlásenie
- ✅ Ochrana trás (middleware)
- ✅ Role-based access control (admin vs user)

### CRUD operácie (63 testov)
- ✅ Vytvorenie (upload, validácia)
- ✅ Čítanie (zobrazenie, viditeľnosť)
- ✅ Úprava (update, autorstvo)
- ✅ Zmazanie (DB + storage)

### Správa súborov (19 testov)
- ✅ Upload viacerých súborov
- ✅ Detekcia typu súboru
- ✅ Uloženie do storage
- ✅ Zmazanie zo storage
- ✅ Správa metadát (názov, veľkosť, typ)

### Download a zdieľanie (26 testov)
- ✅ Kontrola oprávnení
- ✅ ZIP archívovanie
- ✅ Inkrementácia download_count
- ✅ Share tokeny (UUID)
- ✅ Verejné zdieľanie

### Systém Like (11 testov)
- ✅ Pridávanie/odstraňovanie like
- ✅ Počítadlo úľavov
- ✅ Viacerí používatelia
- ✅ Kontrola oprávnení

### Vyhľadávanie a filtrovanie (13 testov)
- ✅ Vyhľadávanie po názvu
- ✅ Vyhľadávanie po popise
- ✅ Filtrovanie podľa kategórie
- ✅ Kombinovanie filtrov
- ✅ Paginalizácia

### Vzťahy modelov (17 testov)
- ✅ Eloquent relationships
- ✅ Computed attributes
- ✅ Type casting

## Očakávané výsledky

Keď sú všetky testy úspešné, uvidíte výstup podobný tomuto:

```
PASS  tests/Feature/AuthenticationTest.php (10 tests)
PASS  tests/Feature/DatasetCRUDTest.php (25 tests)
PASS  tests/Feature/RepositoryTest.php (13 tests)
PASS  tests/Feature/AdminCategoryTest.php (13 tests)
PASS  tests/Feature/AdminUserTest.php (13 tests)
PASS  tests/Feature/AdminDatasetTest.php (10 tests)
PASS  tests/Feature/HomePageTest.php (13 tests)
PASS  tests/Feature/DatasetSharingTest.php (8 tests)
PASS  tests/Feature/DatasetFilesAjaxTest.php (12 tests)
PASS  tests/Feature/DatasetDownloadTest.php (7 tests)
PASS  tests/Feature/DatasetDownloadCountAndLikesTest.php (11 tests)
PASS  tests/Unit/ModelRelationshipsTest.php (17 tests)

Tests:  159 passed (xxx assertions)
Time:   XXXs
```

## Dôležité poznámky

### Database
- Testy používajú `RefreshDatabase` trait, ktorý vytvára a vymazáva testovaciu databázu pred každým testom
- SQLite sa používa pre testy (nie MySQL z docker kontajnera)
- Všetky testovacie údaje sú izolované a neovplyvňujú produkčné dáta

### Storage
- Testy používajú `Storage::fake('local')` na simuláciu file systému
- Fyzické súbory sa neukladajú na disk počas testov
- Všetky file operácie sú testované bez skutočného I/O

### Autorizácia
- Každý test overuje, že iba oprávnení používatelia majú prístup
- Admin má všetky oprávnenia
- Propritor majiteľ má oprávnenia na svoje datasety
- Ostatní nemajú prístup

### Kľúčové testované scenáre

1. **Upload datasetu**:
   - Súbory sa uložia do `storage/app/datasets`
   - Cesty sa uložia do tabuľky `files`
   - Metadata (veľkosť, typ) sú uložené v DB

2. **Stiahnutie datasetu**:
   - Súbory sa zabalajú do ZIP
   - ZIP sa dočasne ukladá v sys_get_temp_dir()
   - `download_count` sa inkrementuje v DB
   - ZIP sa zmaže po stiahnutí

3. **Like systém**:
   - Vzťah many-to-many v tabuľke `dataset_likes`
   - `likes_count` v tabuľke `datasets` sa aktualizuje
   - Transakcii zabraňuje race conditions

4. **Zdieľanie**:
   - Share token je UUID
   - Session flag umožňuje stiahnutie bez prihlásenia
   - URL: `/datasets/share/{token}`

## Riešenie problémov

### Test zlyhá s "Table not found"
Skontrolujte, že `RefreshDatabase` je v teste:
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase {
    use RefreshDatabase;
}
```

### Test zlyhá s "File not found"
Skontrolujte, že `Storage::fake()` je nastavený:
```php
public function test_something(): void {
    Storage::fake('local');
    // ... test code
}
```

### AJAX test zlyhá
Skontrolujte, že je nastavený `X-Requested-With` header:
```php
->withHeader('X-Requested-With', 'XMLHttpRequest')
```

## Ďalšie inštrukcie

- Všetky testy očakávajú `.env.testing` konfiguráciu
- DB_CONNECTION=sqlite, DB_DATABASE=:memory: sa odporúča pre testy
- Keď sú všetky testy zelené ✓, je systém pripravený na produkciu

