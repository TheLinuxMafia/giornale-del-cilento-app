# 🐛 Sistema Debug Logs

## 📋 **Panoramica**

Sistema di logging per debug e monitoraggio dell'applicazione, progettato per essere facilmente rimovibile in futuro.

## 🗄️ **Database**

### **Tabella: `debug_logs`**
```sql
- id (bigint, primary key)
- type (varchar) - Tipo di log: 'login', 'cron', 'api', 'system'
- level (varchar) - Livello: 'info', 'warning', 'error', 'debug'
- message (varchar) - Messaggio descrittivo
- data (json) - Dati aggiuntivi in formato JSON
- user_id (varchar, nullable) - ID utente se applicabile
- ip_address (varchar, nullable) - Indirizzo IP
- user_agent (varchar, nullable) - User agent del browser
- created_at (timestamp)
- updated_at (timestamp)
```

## 🔧 **Backend**

### **Modello: `DebugLog`**
- Relazione con `User`
- Cast automatico del campo `data` in array
- Scope per filtrare per tipo, livello, data

### **Servizio: `DebugLogService`**
Metodi statici per logging:
- `log()` - Log generico
- `logLogin()` - Log accessi utente
- `logCron()` - Log esecuzioni cron
- `logApi()` - Log richieste API
- `logSystem()` - Log eventi sistema
- `cleanOldLogs()` - Pulizia log vecchi
- `getStats()` - Statistiche

### **Controller: `DebugLogController`**
Endpoint API:
- `GET /api/debug-logs` - Lista log con filtri
- `GET /api/debug-logs/stats` - Statistiche
- `GET /api/debug-logs/filters` - Opzioni filtri
- `GET /api/debug-logs/{id}` - Dettaglio singolo log
- `DELETE /api/debug-logs/{id}` - Elimina log
- `POST /api/debug-logs/cleanup` - Pulizia manuale

### **Comando: `CleanDebugLogsCommand`**
```bash
php artisan debug-logs:clean --days=30 --dry-run
```

## 🎨 **Frontend**

### **Componente: `DebugLogsComponent`**
- Tabella paginata con filtri avanzati
- Statistiche in tempo reale
- Visualizzazione dettagli JSON
- Eliminazione singoli log
- Design responsive

### **Route: `/debug-logs`**
- Accessibile solo agli admin
- Protetta da `AuthGuard` + `RoleGuard`

## 📊 **Tipi di Log**

### **Login (`type: 'login'`)**
```php
DebugLogService::logLogin($userId, $username, $request, $success);
```
- Login riusciti e falliti
- IP address e user agent
- Timestamp preciso

### **Cron (`type: 'cron'`)**
```php
DebugLogService::logCron($command, $data, $success);
```
- Esecuzioni comandi schedulati
- Risultati e statistiche
- Errori di esecuzione

### **API (`type: 'api'`)**
```php
DebugLogService::logApi($endpoint, $method, $userId, $request, $data);
```
- Richieste API importanti
- Metodi HTTP e endpoint
- Dati di contesto

### **System (`type: 'system'`)**
```php
DebugLogService::logSystem($event, $data, $level);
```
- Eventi di sistema
- Configurazioni
- Errori applicativi

## 🔄 **Integrazione Automatica**

### **Login Tracking**
- `AuthController::login()` - Log automatico accessi
- Login riusciti e falliti
- Dati utente e IP

### **Cron Tracking**
- `ProcessSyncActionsCommand` - Log esecuzioni sync
- `FetchFeedsCommand` - Log fetch RSS
- Risultati e timing

### **Scheduler**
```php
// Pulizia automatica ogni domenica alle 2:00
Schedule::command('debug-logs:clean --days=30')->weeklyOn(0, '02:00');
```

## 🛠️ **Utilizzo**

### **Logging Manuale**
```php
use App\Services\DebugLogService;

// Log generico
DebugLogService::log('custom', 'Evento personalizzato', [
    'param1' => 'value1',
    'param2' => 'value2'
], 'info');

// Log con utente
DebugLogService::log('api', 'Richiesta speciale', $data, 'info', $userId, $request);
```

### **Visualizzazione Frontend**
1. Accedere come admin
2. Navigare a "Debug Logs" nel menu
3. Utilizzare filtri per trovare log specifici
4. Visualizzare dettagli JSON
5. Eliminare log non necessari

### **Pulizia Database**
```bash
# Pulizia manuale
php artisan debug-logs:clean --days=30

# Test senza eliminare
php artisan debug-logs:clean --days=30 --dry-run

# Pulizia via API
POST /api/debug-logs/cleanup
```

## 📈 **Statistiche Disponibili**

- **Log totali** (ultimi 7 giorni)
- **Login recenti**
- **Esecuzioni cron**
- **Errori**
- **Distribuzione per tipo**
- **Distribuzione per livello**

## 🔒 **Sicurezza**

- Accesso limitato agli admin
- Dati sensibili non loggati
- IP address per audit trail
- Pulizia automatica per privacy

## 🗑️ **Rimozione Futura**

Per rimuovere il sistema in futuro:

1. **Database:**
   ```sql
   DROP TABLE debug_logs;
   ```

2. **Backend:**
   - Rimuovere `DebugLog` model
   - Rimuovere `DebugLogService`
   - Rimuovere `DebugLogController`
   - Rimuovere route API
   - Rimuovere comando `CleanDebugLogsCommand`
   - Rimuovere chiamate `DebugLogService` nei controller

3. **Frontend:**
   - Rimuovere `DebugLogsComponent`
   - Rimuovere route `/debug-logs`
   - Rimuovere link dal menu

4. **Scheduler:**
   - Rimuovere comando cleanup da `console.php`

## 🎯 **Benefici**

- **Debugging** - Tracciamento completo eventi
- **Monitoring** - Statistiche in tempo reale
- **Security** - Audit trail accessi
- **Performance** - Monitoraggio cron jobs
- **Troubleshooting** - Log dettagliati per supporto

## ⚠️ **Note**

- Sistema progettato per essere temporaneo
- Log automatici per eventi critici
- Pulizia automatica per evitare bloat database
- Accesso limitato per privacy
- Facilmente rimovibile quando non più necessario


