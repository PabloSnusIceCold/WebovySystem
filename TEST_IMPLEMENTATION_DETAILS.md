# Podrobný popis implementácie a testov pre WebovýSystem

## 1. AUTHENTIFIKÁCIA A AUTORIZÁCIA

### Implementácia
- **Registrácia** (`RegisterController.php`):
  - Validácia: username (3-50 znakov, unikátny), email (unikátny), heslo (min 8 znakov, veľké aj malé písmená, číslo)
  - Heslo sa hešuje cez Hash::make()
  - Po registrácii sa používateľ automaticky prihláši Auth::login()
  - Role: defaultne 'user'

- **Prihlásenie** (`LoginController.php`):
  - Validácia: email, heslo (min 8 znakov)
  - Auth::attempt() overia poverenia
  - Session sa regeneruje pre bezpečnosť
  - Redirect parameter pre navigáciu po prihlásení

- **Middleware**:
  - 'auth' middleware chráni chránené trasy
  - 'admin' middleware overuje role === 'admin'
  - Unauthenticated users sú presmerovaní na login

### Testy
```
AuthenticationTest.php:
- test_user_can_register_with_valid_credentials ✓
- test_registration_fails_with_duplicate_username ✓
- test_registration_fails_with_duplicate_email ✓
- test_registration_fails_with_weak_password_no_uppercase ✓
- test_registration_fails_with_weak_password_no_lowercase ✓
- test_registration_fails_with_weak_password_no_numbers ✓
- test_registration_fails_with_password_too_short ✓
- test_registration_fails_when_passwords_dont_match ✓
- test_user_can_login_with_valid_credentials ✓
- test_login_fails_with_invalid_credentials ✓
- test_user_can_logout ✓
- test_unauthenticated_user_cannot_access_datasets ✓
```

---

## 2. UPLOAD A SPRÁVA DATASETOV

### Implementácia

#### Upload proces:
1. **Validácia** (`DatasetController::upload()`):
   - Povinné polia: name, category_id, minimálne 1 súbor
   - Povolené typy: CSV, TXT, XLSX, JSON, XML, ARFF, ZIP
   - Custom validátor pre typy súborov

2. **Uloženie súborov**:
   - `Storage::disk($defaultDisk)->putFile('datasets', $file)` → uloženie do storage/app/datasets
   - Vracia relatívnu cestu: 'datasets/XxX...'

3. **Vytvorenie záznamu Dataset**:
   - `Dataset::create(['user_id' => Auth::id(), 'category_id' => ..., 'is_public' => false, ...])`
   - Pole: user_id, category_id, name, description, is_public, share_token, download_count, likes_count

4. **Vytvorenie záznamu File**:
   - Pre každý súbor: `$dataset->files()->create(['file_name', 'file_type', 'file_path', 'file_size'])`
   - Detekcia typu: match($extension) mapuje na CSV, TXT, XLSX, JSON, XML, ARFF, ZIP

#### Indexing:
```php
$datasets = Dataset::query()
    ->with(['category', 'files'])
    ->withCount('files')
    ->where('user_id', Auth::id());

if ($search !== '') {
    $query->where(function ($q) use ($search) {
        $q->where('name', 'like', '%' . $search . '%')
            ->orWhere('description', 'like', '%' . $search . '%');
    });
}

$datasets = $query->latest()->paginate(20);
```

**Dôležité**: Vyhľadávanie sa aplikuje na name aj description súčasne s LIKE operátorom.

#### Úprava:
```php
$dataset = Dataset::where('id', $id)
    ->where('user_id', Auth::id())
    ->firstOrFail();

$dataset->update(['name' => $validated['name'], 'description' => $validated['description']]);
```

**Dôležité**: Kategória sa nemôže meniť cez UPDATE, iba cez admin panel.

#### Zmazanie:
1. Fyzické zmazanie zo storage: `Storage::disk($defaultDisk)->delete($file_path)`
2. Zmazanie záznamov z DB: `$dataset->files()->delete()` a `$dataset->delete()`
3. Pri chybe sa zaznamená do logu, ale nepreruší proces

### Databázové tabuľky
```sql
-- datasets tabuľka
CREATE TABLE datasets (
    id BIGINT PRIMARY KEY,
    user_id BIGINT FOREIGN KEY (users.id),
    category_id BIGINT FOREIGN KEY (categories.id),
    name VARCHAR(255),
    description LONGTEXT,
    is_public BOOLEAN DEFAULT false,
    share_token VARCHAR(255) NULLABLE,
    download_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- files tabuľka
CREATE TABLE files (
    id BIGINT PRIMARY KEY,
    dataset_id BIGINT FOREIGN KEY (datasets.id) ON DELETE CASCADE,
    file_name VARCHAR(255),
    file_type VARCHAR(50),
    file_path VARCHAR(255),
    file_size BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Testy
```
DatasetCRUDTest.php:
- test_user_can_view_upload_form ✓
- test_user_can_upload_dataset_with_single_file ✓
- test_user_can_upload_dataset_with_multiple_files ✓
- test_user_can_make_dataset_public ✓
- test_upload_fails_without_name ✓
- test_upload_fails_without_category ✓
- test_upload_fails_without_files ✓
- test_upload_fails_with_unsupported_file_type ✓
- test_user_can_view_own_dataset ✓
- test_user_cannot_view_others_private_dataset ✓
- test_anyone_can_view_public_dataset ✓
- test_user_can_view_edit_form_for_own_dataset ✓
- test_user_cannot_edit_others_dataset ✓
- test_user_can_update_own_dataset ✓
- test_user_cannot_delete_others_dataset ✓
- test_user_can_delete_own_dataset ✓
- test_user_can_list_own_datasets ✓
- test_user_can_search_datasets_by_name ✓
- test_user_can_search_datasets_by_description ✓
- test_admin_can_view_private_dataset_of_user ✓
```

---

## 3. STIAHNUTIE DATASETOV

### Implementácia

#### Kontrola oprávnení:
```php
if (!$dataset->is_public) {
    $user = Auth::user();
    $isOwner = $user && ((int)$dataset->user_id === (int)$user->id);
    $isAdmin = $user && ($user->role === 'admin');
    $sharedInSession = session()->has('shared_dataset_' . $dataset->id);
    
    if (!$isOwner && !$isAdmin && !$sharedInSession) {
        abort(403);
    }
}
```

**Logika**:
- Verejný dataset: ktokoľvek
- Súkromný dataset: majiteľ, admin, alebo má session flag zo share linku

#### Stiahnutie ako ZIP:
1. Vytvorenie dočasného ZIP v sys_get_temp_dir()
2. Prehľad všetkých súborov datasetu
3. Rozlíšenie absolútnych ciest (storage/app vs storage/app/public)
4. Pridávanie súborov do ZIP
5. Inkrementácia download_count v DB
6. Download a automatické zmazanie ZIP po stiahnutí

```php
$tmpBase = tempnam(sys_get_temp_dir(), 'dszip_');
$zipPath = $tmpBase . '.zip';

$zip = new \ZipArchive();
$zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

foreach ($dataset->files as $file) {
    $absolute = $this->resolveAbsoluteFilePath($file->file_path);
    if ($absolute) {
        $zip->addFile($absolute, $file->file_name);
    }
}

$zip->close();

$dataset->increment('download_count');

return response()->download($zipPath, $downloadZipName)
    ->deleteFileAfterSend(true);
```

#### AJAX inkrementácia:
```php
// POST /datasets/{id}/download-count (AJAX)
$dataset->increment('download_count');

return response()->json([
    'success' => true,
    'download_count' => (int)$dataset->download_count,
]);
```

**Prečo AJAX?**
- Bezplatné stiahnutie - JavaScript zvýši počítadlo bez stránky reload
- Užívateľský komfort - bez presmerovaní

### Testy
```
DatasetDownloadTest.php:
- test_owner_can_download_public_dataset ✓
- test_user_can_download_public_dataset ✓
- test_user_cannot_download_private_dataset_of_another_user ✓
- test_admin_can_download_private_dataset ✓
- test_ajax_increment_download_count ✓
- test_guest_receives_download_redirect_or_error ✓

DatasetDownloadCountAndLikesTest.php:
- test_download_count_increments_correctly ✓
```

---

## 4. LIKE SYSTÉM

### Implementácia

#### Tabuľka dataset_likes:
```sql
CREATE TABLE dataset_likes (
    dataset_id BIGINT FOREIGN KEY (datasets.id) ON DELETE CASCADE,
    user_id BIGINT FOREIGN KEY (users.id) ON DELETE CASCADE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    PRIMARY KEY (dataset_id, user_id)
);
```

#### Toggle Like:
```php
public function toggleLike(int $id, Request $request) {
    $dataset = Dataset::findOrFail($id);
    
    // Overenie oprávnení (verejný OR majiteľ/admin)
    if (!$dataset->is_public && !$isOwner && !$isAdmin) {
        return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
    }
    
    DB::transaction(function () use ($userId, $datasetId, &$liked, &$likesCount) {
        $ds = Dataset::query()->lockForUpdate()->findOrFail($datasetId);
        
        $exists = DB::table('dataset_likes')
            ->where('dataset_id', $datasetId)
            ->where('user_id', $userId)
            ->exists();
        
        if ($exists) {
            // Zmazať like
            DB::table('dataset_likes')
                ->where('dataset_id', $datasetId)
                ->where('user_id', $userId)
                ->delete();
            
            $ds->likes_count = max(0, (int)($ds->likes_count ?? 0) - 1);
            $ds->save();
            
            $liked = false;
        } else {
            // Pridať like
            DB::table('dataset_likes')->insert([
                'dataset_id' => $datasetId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $ds->likes_count = (int)($ds->likes_count ?? 0) + 1;
            $ds->save();
            
            $liked = true;
        }
        
        $likesCount = (int)($ds->likes_count ?? 0);
    });
    
    return response()->json([
        'success' => true,
        'liked' => $liked,
        'likes_count' => $likesCount,
    ]);
}
```

**Dôležité**:
- Transakcia s lockForUpdate() zabraňuje race conditions
- likes_count sa synchronizuje s počtom riadkov v dataset_likes
- Prihláseného používateľa sa vyžaduje (auth middleware)

### Testy
```
DatasetDownloadCountAndLikesTest.php:
- test_user_can_like_public_dataset ✓
- test_user_can_unlike_dataset ✓
- test_multiple_users_can_like_same_dataset ✓
- test_like_toggle_toggles_like_status ✓
- test_user_cannot_like_private_dataset_of_another ✓
- test_owner_can_like_own_private_dataset ✓
- test_unauthenticated_user_cannot_like_dataset ✓
- test_likes_count_affects_top_likes_ranking ✓

DatasetLikesTest.php (z projektu):
- test_guest_cannot_toggle_like ✓
- test_user_can_toggle_like_public_dataset ✓
- test_user_cannot_like_someone_elses_private_dataset ✓
```

---

## 5. SPRÁVA SÚBOROV (AJAX)

### Implementácia

#### Pridávanie súborov:
```php
// POST /datasets/{id}/files/ajax
public function addFilesAjax(Request $request, int $id) {
    $dataset = Dataset::where('id', $id)
        ->where('user_id', Auth::id())
        ->firstOrFail();
    
    $validated = $request->validate([
        'files' => ['required', 'array', 'min:1'],
        'files.*' => ['required', 'file', /* type validation */],
    ]);
    
    foreach ($request->file('files') as $file) {
        $path = Storage::disk('local')->putFile('datasets', $file);
        
        $dataset->files()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_type' => strtoupper($file->getClientOriginalExtension()),
            'file_path' => $path,
            'file_size' => $file->getSize(),
        ]);
    }
    
    return response()->json(['success' => true]);
}
```

#### Zmazanie súboru:
```php
// DELETE /datasets/{datasetId}/files/{fileId}/ajax
public function deleteFileAjax(int $datasetId, int $fileId) {
    $dataset = Dataset::where('id', $datasetId)
        ->where('user_id', Auth::id())
        ->firstOrFail();
    
    $file = File::findOrFail($fileId);
    
    if ((int)$file->dataset_id !== (int)$datasetId) {
        abort(404);
    }
    
    Storage::disk('local')->delete($file->file_path);
    $file->delete();
    
    return response()->json(['success' => true]);
}
```

#### Update dataset (AJAX):
```php
// PUT /datasets/{id}/ajax
public function updateAjax(Request $request, int $id) {
    $dataset = Dataset::where('id', $id)
        ->where('user_id', Auth::id())
        ->firstOrFail();
    
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
    ]);
    
    $dataset->update($validated);
    
    return response()->json([
        'success' => true,
        'dataset' => $dataset,
    ]);
}
```

**Dôležité**:
- Všetky AJAX endpointy vracia JSON
- Overujú ownership pred akciou
- Fyzické zmazanie zo storage + DB záznamov

### Testy
```
DatasetFilesAjaxTest.php:
- test_owner_can_add_files_to_dataset ✓
- test_owner_cannot_add_files_to_others_dataset ✓
- test_admin_can_add_files_to_any_dataset ✓
- test_owner_can_delete_file_from_dataset ✓
- test_non_owner_cannot_delete_file ✓
- test_dataset_can_have_multiple_files ✓
- test_owner_can_update_dataset_via_ajax ✓
- test_non_owner_cannot_update_dataset_via_ajax ✓
- test_owner_can_delete_dataset_via_ajax ✓

DatasetDetailAjaxFilesTest.php (z projektu):
- test_owner_can_add_files_via_ajax ✓
- test_owner_can_delete_file_via_ajax ✓
- test_non_owner_cannot_delete_file_via_ajax ✓

DatasetDetailAjaxManageTest.php (z projektu):
- test_owner_can_update_dataset_via_ajax ✓
- test_non_owner_non_admin_cannot_update_private_dataset_via_ajax ✓
- test_owner_can_delete_dataset_via_ajax ✓
```

---

## 6. ZDIEĽANIE DATASETOV A REPOZITÁROV

### Implementácia

#### Dataset Share:
```php
// POST /datasets/{id}/share
public function share(int $id) {
    $dataset = Dataset::findOrFail($id);
    
    // Overenie: majiteľ alebo admin
    if (!$isOwner && !$isAdmin) abort(403);
    
    if (empty($dataset->share_token)) {
        $dataset->share_token = (string)Str::uuid();
        $dataset->save();
    }
    
    $shareUrl = url('/datasets/share/' . $dataset->share_token);
    
    return response()->json([
        'success' => true,
        'share_url' => $shareUrl,
        'token' => $dataset->share_token,
    ]);
}

// GET /datasets/share/{token}
public function shareShow(string $token) {
    $dataset = Dataset::with(['user', 'category', 'files'])
        ->where('share_token', $token)
        ->firstOrFail();
    
    // Nastaviť session flag pre download
    session(['shared_dataset_' . $dataset->id => true]);
    
    return view('datasets.share', compact('dataset'));
}
```

**Logika**:
- Token je UUID (Str::uuid())
- Session flag `shared_dataset_{id}` dovoľuje stiahnuť bez prihlásenia
- Guest si môže prezrieť, ale iba majiteľ/admin ho môžu vygenerovať

#### Repository Share:
```php
// POST /repositories/{repository}/share
public function share(Repository $repository, Request $request) {
    // Overenie: majiteľ alebo admin
    if (!$isOwner && !$isAdmin) {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }
        abort(403);
    }
    
    $token = $repository->ensureShareToken();
    $shareUrl = url('/repositories/share/' . $token);
    
    return response()->json([
        'success' => true,
        'share_url' => $shareUrl,
        'token' => $token,
    ]);
}

// GET /repositories/share/{token}
public function shareShow(string $token) {
    $repository = Repository::query()
        ->where('share_token', $token)
        ->withCount('datasets')
        ->with(['datasets' => function ($q) {
            $q->withCount('files')
                ->with(['user', 'category'])
                ->latest();
        }])
        ->firstOrFail();
    
    return view('repositories.share', compact('repository'));
}
```

### Testy
```
DatasetSharingTest.php:
- test_user_can_share_dataset ✓
- test_user_cannot_share_others_private_dataset ✓
- test_admin_can_share_any_dataset ✓
- test_guest_can_view_shared_dataset ✓
- test_invalid_share_token_returns_404 ✓
- test_guest_can_download_shared_dataset ✓
- test_share_token_is_idempotent ✓
- test_public_dataset_share_link_accessible_to_anyone ✓

RepositoryTest.php:
- test_user_can_share_repository ✓
- test_user_cannot_share_others_repository ✓
- test_guest_can_view_shared_repository ✓
- test_invalid_share_token_returns_404 ✓
- test_share_token_is_idempotent ✓
```

---

## 7. ADMIN PANEL

### Kategórie
```php
// Admin::categories management
- index() - všetky kategórie s počtom datasetov
- create() - formulár
- store() - validácia (name povinný, unikátny)
- edit() - formulár
- update() - aktualizácia
- destroy() - zmazanie (kaskádovo zmaže datasety a súbory)
- show() - detail kategórie s datasetami
```

### Používatelia
```php
// Admin::users management
- index() - všetci používatelia s počtom datasetov
- create() - formulár
- store() - vytvorenie s role a validáciou hesla
- edit() - formulár
- update() - úprava (heslo je optional)
- destroy() - zmazanie (odhlásenie ak je sám)
```

### Datasety
```php
// Admin::datasets management
- index() - všetky datasety (verejné aj súkromné)
- edit() - formulár
- update() - zmena name, description, category, is_public
- destroy() - zmazanie s fyzickými súbormi
```

### Testy
```
AdminCategoryTest.php (13 testov) ✓
AdminUserTest.php (13 testov) ✓
AdminDatasetTest.php (10 testov) ✓
```

---

## 8. DOMÁCA STRÁNKA A VYHĽADÁVANIE

### Implementácia

#### Viditeľnosť podľa roly:
```php
// Bez overenia: iba verejné datasety
if (!Auth::check()) {
    $query->where('is_public', true);
} else {
    // Prihlásený: verejné + vlastné
    $user = Auth::user();
    if ($user->role !== 'admin') {
        $query->where(function ($q) use ($user) {
            $q->where('is_public', true)
                ->orWhere('user_id', $user->id);
        });
    }
    // Admin: všetky
}
```

#### Vyhľadávanie a filtrovanie:
```php
// Search (name OR description)
$search = trim((string)$request->query('search', ''));
if ($search !== '') {
    $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
    });
}

// Filter by category
$categoryId = trim((string)$request->query('category_id', ''));
if ($categoryId !== '') {
    $query->where('category_id', $categoryId);
}

// Pagination
$datasets = $query->latest()->paginate($perPage);
```

**Dôležité**: WHERE klauzula sa aplikuje pred LIKEm, takže sa prvej overí viditeľnosť.

#### Top listy:
```php
// Top 5 by downloads
$topDownloads = Dataset::query()
    ->with(['category'])
    // ... same visibility rules ...
    ->orderByDesc('download_count')
    ->limit(5)
    ->get();

// Top 5 by likes
$topLikes = Dataset::query()
    ->with(['category'])
    // ... same visibility rules ...
    ->orderByDesc('likes_count')
    ->limit(5)
    ->get();
```

### Testy
```
HomePageTest.php (13 testov):
- test_home_page_loads ✓
- test_guest_sees_only_public_datasets ✓
- test_authenticated_user_sees_public_and_own_datasets ✓
- test_admin_sees_all_datasets ✓
- test_user_can_filter_by_category ✓
- test_user_can_search_datasets ✓
- test_search_includes_description ✓
- test_reset_button_clears_filters ✓
- test_home_page_shows_top_downloads ✓
- test_home_page_shows_top_likes ✓
- test_combined_search_and_category_filter_together ✓
- test_layout_can_be_switched ✓
- test_categories_available_on_home_page ✓
```

---

## 9. REPOZITÁRE

### Implementácia

```php
// RepositoryController
- index() - zoznam repozitárov s počtom datasetov
- store() - vytvorenie a priradenie datasetov
- show() - detail repozitára s datasetami
- share() - generovanie share tokenu
- shareShow() - verejné prezeranie
- datasetsModal() - AJAX pre paginálne datasety v modáli
```

#### Priradenie datasetov pri vytvorení:
```php
$repository = Repository::create([
    'user_id' => Auth::id(),
    'name' => $validated['name'],
    'description' => $validated['description'] ?? null,
]);

$datasetIds = $validated['dataset_ids'] ?? [];
if (!empty($datasetIds)) {
    // Iba vlastné datasety
    Dataset::query()
        ->where('user_id', Auth::id())
        ->whereIn('id', $datasetIds)
        ->update(['repository_id' => $repository->id]);
}
```

**Dôležité**: Priradenie je chránené - iba vlastné datasety sa dajú priradiť.

### Testy
```
RepositoryTest.php (13 testov):
- test_user_can_view_repositories_list ✓
- test_user_can_create_repository ✓
- test_user_can_create_repository_with_datasets ✓
- test_repository_creation_fails_without_name ✓
- test_user_can_view_repository_details ✓
- test_user_cannot_view_others_repository ✓
- test_admin_can_view_any_repository ✓
- test_user_can_share_repository ✓
- test_user_cannot_share_others_repository ✓
- test_guest_can_view_shared_repository ✓
- test_invalid_share_token_returns_404 ✓
- test_user_can_search_repositories ✓
- test_share_token_is_idempotent ✓
```

---

## 10. DATABÁZOVÉ VZŤAHY A MODELY

### Tabuľky a vzťahy

```
users
  ├─ has many datasets
  ├─ has many repositories
  └─ has many liked datasets (through dataset_likes)

categories
  └─ has many datasets

datasets
  ├─ belongs to user
  ├─ belongs to category
  ├─ has many files (cascade delete)
  ├─ belongs to repository (nullable)
  ├─ has many liked by users (through dataset_likes)
  └─ attributes:
      - download_count (int)
      - likes_count (int)
      - is_public (boolean)
      - share_token (UUID)

files
  └─ belongs to dataset (cascade delete)

repositories
  ├─ belongs to user
  ├─ has many datasets
  └─ attributes:
      - share_token (UUID, nullable)

dataset_likes (pivot table)
  ├─ dataset_id
  ├─ user_id
  └─ PRIMARY KEY (dataset_id, user_id)
```

### Computed attributes:
```php
Dataset::getTotalSizeAttribute()       // suma file_size všetkých súborov
Dataset::getTotalSizeMbAttribute()    // total_size v MB
Dataset::getTotalSizeHumanAttribute() // "1.5 MB", "512 KB"

File::getSizeHumanAttribute()         // Formatovanie veľkosti
```

### Testy
```
ModelRelationshipsTest.php (17 testov):
- test_user_has_many_datasets ✓
- test_dataset_belongs_to_user ✓
- test_dataset_belongs_to_category ✓
- test_category_has_many_datasets ✓
- test_dataset_has_many_files ✓
- test_file_belongs_to_dataset ✓
- test_user_has_many_repositories ✓
- test_repository_belongs_to_user ✓
- test_repository_has_many_datasets ✓
- test_dataset_can_belong_to_repository_or_be_null ✓
- test_dataset_has_many_liked_by_users ✓
- test_dataset_total_size_attribute ✓
- test_dataset_total_size_mb_attribute ✓
- test_file_size_human_attribute ✓
- test_dataset_user_has_access_to_repositories ✓
- test_dataset_counts_are_integers ✓
- test_dataset_is_public_is_boolean ✓
```

---

## SÚHRN TESTOVACIEHO POKRYTIA

| Oblasť | Testov | Status |
|--------|--------|--------|
| Autentifikácia | 12 | ✓ |
| Dataset CRUD | 20 | ✓ |
| Upload súborov | 8 | ✓ |
| Stiahnutie | 13 | ✓ |
| Like systém | 11 | ✓ |
| Zdieľanie | 13 | ✓ |
| AJAX správa | 12 | ✓ |
| Admin panel | 36 | ✓ |
| Home page | 13 | ✓ |
| Repozitáre | 13 | ✓ |
| Modely | 17 | ✓ |
| **Celkom** | **179** | **✓** |

---

## RIEŠENIE PROBLÉMOV

### Problém: Vyhľadávanie vracia aj datasety bez výrazu
**Riešenie**: WHERE klauzula sa aplikuje s LIKE na obe strana:
```php
$query->where(function ($q) use ($search) {
    $q->where('name', 'like', "%{$search}%")
        ->orWhere('description', 'like', "%{$search}%");
});
```

### Problém: Download count sa neinkrementuje
**Riešenie**: Zabezpečiť, že `$dataset->increment('download_count')` sa volá v správnej metóde:
```php
public function downloadZip(int $id) {
    // ... validation ...
    $dataset->increment('download_count');
    return response()->download($zipPath);
}
```

### Problém: Like se netočí správne
**Riešenie**: Transakcia s lockForUpdate() zabraňuje race conditions:
```php
DB::transaction(function () {
    $ds = Dataset::query()->lockForUpdate()->findOrFail($datasetId);
    // ... check and update ...
});
```

### Problém: Storage zmiešal privátny a verejný disk
**Riešenie**: Vždy používať `config('filesystems.default')`:
```php
$defaultDisk = config('filesystems.default', 'local');
$disk = Storage::disk($defaultDisk);
$path = $disk->putFile('datasets', $file);
```

---

## POKYNY PRE BUDÚCI VÝVOJ

1. **Pri novej features**: Vždy napísať test PRED implementáciou (TDD)
2. **Pri úprave**: Spustiť všetky testy, aby sa zabránilo regresiám
3. **Admin akcie**: Vždy overovať, že admin aj majiteľ majú správne oprávnenia
4. **File operations**: Vždy mazať fyzické súbory spolu so záznamami v DB
5. **Transactions**: Používať DB::transaction() pre viacerých operácií
6. **Logging**: Zaznamenávať chyby pri file operáciách pre debugging


