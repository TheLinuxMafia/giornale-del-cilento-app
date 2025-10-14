# 🔐 Sistema Super User

## 📋 **Panoramica**

Sistema di controllo accessi granulare che identifica utenti con privilegi elevati per funzionalità specifiche come la visualizzazione dei debug logs.

## 🗄️ **Database**

### **Campo Aggiunto: `is_super_user`**
```sql
ALTER TABLE users ADD COLUMN is_super_user BOOLEAN DEFAULT FALSE;
CREATE INDEX idx_users_is_super_user ON users(is_super_user);
```

## 🔧 **Backend**

### **Modello User - Metodi Aggiunti:**
```php
// Controlli super user
public function isSuperUser(): bool
public function canAccessAdmin(): bool  
public function canViewDebugLogs(): bool

// Metodo admin legacy (backward compatibility)
public function isAdmin(): bool // Ora include anche super user
```

### **Middleware: `SuperUserMiddleware`**
- Controlla autenticazione
- Verifica privilegi super user
- Gestisce redirect e errori JSON
- Registrato come `super.user`

### **Comando: `ManageSuperUsersCommand`**
```bash
# Lista super user
php artisan users:super list

# Aggiungi super user
php artisan users:super add 1
php artisan users:super add --email=admin@example.com
php artisan users:super add --username=admin

# Rimuovi super user
php artisan users:super remove 1

# Toggle super user
php artisan users:super toggle 1
```

### **API Endpoints Protetti:**
```php
// Debug Logs (Super User only)
Route::middleware('super.user')->group(function () {
    Route::get('/debug-logs', [DebugLogController::class, 'index']);
    Route::get('/debug-logs/stats', [DebugLogController::class, 'stats']);
    Route::get('/debug-logs/filters', [DebugLogController::class, 'filters']);
    Route::get('/debug-logs/{debugLog}', [DebugLogController::class, 'show']);
    Route::delete('/debug-logs/{debugLog}', [DebugLogController::class, 'destroy']);
    Route::post('/debug-logs/cleanup', [DebugLogController::class, 'cleanup']);
});
```

## 🎨 **Frontend**

### **AuthService - Metodi Aggiunti:**
```typescript
isSuperUser(): boolean
canAccessAdmin(): boolean
canViewDebugLogs(): boolean
```

### **Guard: `SuperUserGuard`**
- Estende `AuthGuard`
- Controlla privilegi super user
- Redirect a dashboard con errore se non autorizzato

### **Interfaccia User Aggiornata:**
```typescript
export interface User {
  id: number;
  wpUserId: number;
  name: string;
  email: string;
  roles: string[];
  is_super_user?: boolean; // Nuovo campo
}
```

### **Route Protette:**
```typescript
{
  path: 'debug-logs',
  loadComponent: () => import('./components/debug-logs/debug-logs.component'),
  canActivate: [AuthGuard, SuperUserGuard] // Solo super user
}
```

### **Menu Condizionale:**
```html
<a mat-list-item routerLink="/debug-logs" 
   *ngIf="authService.canViewDebugLogs()">
  <mat-icon>bug_report</mat-icon>
  <span>Debug Logs</span>
</a>
```

## 🔒 **Sicurezza**

### **Controlli Multi-Livello:**
1. **Frontend Guard** - Blocca accesso alle route
2. **Backend Middleware** - Protegge API endpoints
3. **Menu Condizionale** - Nasconde opzioni non autorizzate
4. **Database Index** - Performance per query super user

### **Backward Compatibility:**
- Metodo `isAdmin()` include anche super user
- Route admin esistenti continuano a funzionare
- Sistema graduale di migrazione

## 🎯 **Utilizzo**

### **Creare Super User:**
```bash
# Via comando
php artisan users:super add 1

# Via database
UPDATE users SET is_super_user = true WHERE id = 1;
```

### **Verificare Super User:**
```bash
# Lista super user
php artisan users:super list

# Test accesso
curl -H "Authorization: Bearer TOKEN" \
     http://localhost:8000/api/debug-logs/stats
```

### **Frontend:**
1. Login con super user
2. Menu mostra "Debug Logs"
3. Accesso diretto a `/debug-logs`
4. API calls automaticamente autorizzate

## 📊 **Funzionalità Super User**

### **Attualmente Disponibili:**
- ✅ **Debug Logs** - Visualizzazione e gestione log
- ✅ **Accesso Admin** - Controllo generale admin

### **Estendibili:**
- 🔄 **System Settings** - Configurazioni avanzate
- 🔄 **User Management** - Gestione utenti completa
- 🔄 **Backup/Restore** - Operazioni di sistema
- 🔄 **Audit Logs** - Log di sicurezza avanzati

## 🔄 **Migrazione da Admin**

### **Strategia Graduale:**
1. **Fase 1:** Super user per debug logs ✅
2. **Fase 2:** Estendere ad altre funzionalità
3. **Fase 3:** Deprecare sistema admin legacy
4. **Fase 4:** Unificare in super user system

### **Mapping Ruoli:**
```php
// Legacy admin check
$user->isAdmin() // admin role OR super user

// Nuovo super user check  
$user->isSuperUser() // solo super user flag

// Controllo granulare
$user->canViewDebugLogs() // super user specifico
```

## 🛠️ **Manutenzione**

### **Comandi Utili:**
```bash
# Lista super user
php artisan users:super list

# Aggiungi super user
php artisan users:super add --email=admin@example.com

# Rimuovi super user
php artisan users:super remove 1

# Toggle super user
php artisan users:super toggle 1
```

### **Query Database:**
```sql
-- Lista super user
SELECT id, name, email, is_super_user FROM users WHERE is_super_user = true;

-- Conta super user
SELECT COUNT(*) FROM users WHERE is_super_user = true;

-- Aggiungi super user
UPDATE users SET is_super_user = true WHERE email = 'admin@example.com';
```

## ⚠️ **Note Importanti**

### **Sicurezza:**
- Super user hanno accesso completo al sistema
- Usare con parsimonia
- Monitorare accessi via debug logs
- Rotazione periodica dei privilegi

### **Performance:**
- Indice database per query veloci
- Cache user permissions se necessario
- Middleware ottimizzato per performance

### **Backup:**
- Backup database prima di modifiche
- Documentare super user attivi
- Procedure di emergenza per accesso

## 🎯 **Benefici**

- **Granularità** - Controllo preciso dei privilegi
- **Sicurezza** - Accesso limitato a funzionalità critiche
- **Scalabilità** - Facilmente estendibile
- **Audit** - Tracciamento completo degli accessi
- **Flessibilità** - Sistema modulare e configurabile


