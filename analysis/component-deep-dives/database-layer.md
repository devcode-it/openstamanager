# Database Layer – Analisi Approfondita
> Fase 2 – Component Analysis

## Classe `Database` (src/Database.php)

### Pattern: Singleton + Eloquent Capsule Wrapper
`Database` estende `Util\Singleton` e wrappa `Illuminate\Database\Capsule\Manager`.
Non usa mai Eloquent direttamente nelle query legacy: espone una propria API imperativa sopra PDO/Capsule.

```php
// Singleton globale – usato ovunque come helper
$dbo = database(); // = Database::getInstance()
```

### API di Query – Tre livelli

**Livello 1 – API imperativa custom (legacy, usata nei moduli PHP)**
```php
$dbo->fetchArray($query, $params)  // → array associativo
$dbo->fetchOne($query, $params)    // → primo record (+ LIMIT 1 automatico)
$dbo->fetchNum($query)             // → COUNT(*)
$dbo->query($query, $params)       // → lastInsertId (o 1 se no insert)
$dbo->select($table, $fields, $joins, $conditions, $order, $limit)
$dbo->selectOne($table, $fields, $conditions, $order)
$dbo->insert($table, $array)
$dbo->update($table, $array, $conditions)
$dbo->delete($table, $conditions)
```

**Livello 2 – Fluent Builder (accesso diretto a Eloquent Query Builder)**
```php
$dbo->table('co_documenti')->where(...)->get()
$dbo->raw('NOW()')
```

**Livello 3 – Eloquent Models (nei src/ dei moduli)**
```php
Fattura::with('tipo', 'stato')->find($id_record)
```

### Metodi speciali per relazioni M:N
```php
$dbo->sync($table, $conditions, $list)   // diff + insert + delete
$dbo->attach($table, $conditions, $list) // INSERT IGNORE per race condition safety
$dbo->detach($table, $conditions, $list) // rimuove combinazioni
```

### Transazioni
```php
$dbo->beginTransaction() / commitTransaction() / rollbackTransaction()
```

### Note tecniche importanti
- **charset**: utf8mb4 / utf8mb4_general_ci (corretto per emoji e caratteri speciali)
- **sql_mode = ''** alla connessione (compatibilità query legacy)
- `isInstalled()` → verifica esistenza di `zz_modules`
- `prepare()` → usa `PDO::quote()` per escape manuale nelle query raw

### Pattern legacy ancora presente nei moduli
```php
// Diffuso nei moduli: interpolazione con prepare() invece di veri prepared statements
$dbo->fetchOne('SELECT * FROM co_documenti WHERE id='.prepare($id_record));
```
Sicuro (PDO::quote) ma non usa binding nativo. Il refactoring verso il Fluent Builder è in corso.
