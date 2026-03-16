# Sistema Auth (AuthOSM) – Analisi Approfondita
> Fase 2 – Component Analysis

## Panoramica
`AuthOSM` (src/AuthOSM.php, ~1368 righe) è un Singleton che gestisce
tutti i meccanismi di autenticazione del gestionale.

---

## Metodi di autenticazione supportati

### 1. Login classico (username + password)
```php
auth_osm()->attempt($username, $password)
```
- Hash bcrypt (`PASSWORD_BCRYPT`)
- Verifica account abilitato
- Verifica presenza di almeno un modulo con permessi
- Genera `session_token` univoco salvato in `zz_users.session_token`
- Salva `id_utente` in `$_SESSION`

### 2. OAuth2 (Microsoft Azure, Google, Keycloak)
```php
auth_osm()->attempt($username, $password, $force = true)
```
- Flusso gestito da `oauth2_login.php` + `league/oauth2-*`
- Configurazione provider in tabella `zz_oauth2`
- `$force=true` bypassa la verifica password (già autenticato via OAuth)

### 3. Token OTP (accesso condiviso)
```php
auth_osm()->attemptOTPLogin($token, $otp_code)
```
- Token in `zz_otp_tokens`, OTP di 6 caratteri (charset sicuro, no caratteri ambigui)
- OTP crittograficamente sicuro via `random_int()`
- Supporta accesso con utente associato o sessione virtuale (solo modulo target)
- Configurable per date di validità

### 4. Token diretto (senza OTP)
```php
auth_osm()->attemptTokenLogin($token)
```
- Per link condivisi che non richiedono OTP
- Stesso meccanismo di zz_otp_tokens ma senza verifica codice

### 5. API Token (Sanctum)
- Riconosciuto in costruzione via `API::isAPIRequest()`
- Lookup in `zz_tokens` per `id_utente`

---

## Protezioni di sicurezza

### Brute Force Protection
- Tabella `zz_logs`: conta i tentativi falliti per IP
- Soglia: 3 tentativi in 180 secondi → lockout automatico
- UI con countdown JavaScript lato client

### Single Session Control
- `zz_users.session_token`: token univoco di sessione
- Impostazione "Abilita controllo sessione singola" → blocca login multipli
- Verifica `isOnline()` sull'utente (basata su operazioni recenti)

### Intended URL
- URL richiesta prima del login salvato in sessione
- Post-login redirect → verifica permessi modulo prima di eseguire redirect
- `canAccessIntendedUrl()` → controlla `Modules::getPermission()`

---

## Tabelle DB coinvolte

| Tabella | Ruolo |
|---|---|
| `zz_users` | Utenti (username, password, enabled, session_token, idgruppo) |
| `zz_groups` | Gruppi/ruoli (nome, id_module_start) |
| `zz_permissions` | Permessi modulo per gruppo (idmodule, idgruppo, permessi: r/rw/-) |
| `zz_logs` | Log accessi (username, ip, stato, created_at) |
| `zz_tokens` | Token API Sanctum |
| `zz_otp_tokens` | Token OTP/condivisi (token, enabled, last_otp, id_utente, id_module_target) |
| `zz_oauth2` | Configurazioni OAuth2 provider |

---

## Stati di autenticazione
```php
'success'          // login riuscito
'failed'           // credenziali errate
'disabled'         // account disabilitato
'unauthorized'     // nessun permesso su moduli
'already_logged_in' // singola sessione attiva
```

---

## Helper globali
```php
auth_osm()         // = AuthOSM::getInstance()
AuthOSM::check()   // bool: utente autenticato?
AuthOSM::admin()   // bool: è admin?
AuthOSM::user()    // → Model\User corrente
AuthOSM::logout()  // distrugge sessione
AuthOSM::firstModule() // primo modulo navigabile
```
