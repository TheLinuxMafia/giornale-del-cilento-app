# 🧪 Test Autenticazione WordPress

## ✅ Sistema di Test Implementato

Il sistema di autenticazione WordPress è ora completamente funzionante con utenti di test predefiniti.

## 🔐 Utenti di Test Disponibili

### 1. **Amministratore**
- **Username**: `admin@giornale.it`
- **Password**: `admin123`
- **Ruolo**: `administrator`
- **ID WordPress**: `1`

### 2. **Redattore**
- **Username**: `redattore@giornale.it`
- **Password**: `editor123`
- **Ruolo**: `editor`
- **ID WordPress**: `2`

### 3. **Autore**
- **Username**: `autore@giornale.it`
- **Password**: `author123`
- **Ruolo**: `author`
- **ID WordPress**: `3`

## 🚀 Come Testare

### Frontend (Interfaccia Web)
1. Vai su `http://localhost:4200`
2. Clicca su "Mostra utenti di test"
3. Seleziona uno degli utenti di test
4. Il sistema effettuerà automaticamente il login
5. Verrai reindirizzato alla dashboard

### Backend (API)
```bash
# Test Amministratore
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin@giornale.it","password":"admin123"}'

# Test Redattore
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"redattore@giornale.it","password":"editor123"}'

# Test Autore
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"autore@giornale.it","password":"author123"}'
```

## 📋 Risposta API di Successo

```json
{
  "status": "success",
  "data": {
    "user": {
      "id": 1,
      "wpUserId": 2,
      "name": "Redattore Capo",
      "email": "redattore@giornale.it",
      "roles": ["editor"]
    },
    "token": "1|yvhlKvE36hwT2tfIobiCLjdYF821Oh6tro60jOQGb93cd7a1",
    "wpToken": "wp_test_token_1758975471_2",
    "wpProfileUrl": "http://wa.linuxit.it/wp-admin/profile.php?user_id=2",
    "wpDashboardUrl": "http://wa.linuxit.it/wp-admin/"
  }
}
```

## 🔗 Funzionalità WordPress

Dopo il login, gli utenti possono:

1. **Accedere al Profilo WordPress**: Clicca su "Profilo WordPress" nella dashboard
2. **Accedere alla Dashboard WordPress**: Clicca su "Dashboard WordPress" nella dashboard
3. **Gestire Contenuti**: Creare, modificare e pubblicare articoli
4. **Gestire Feed RSS**: Aggiungere e configurare feed RSS
5. **Collaborazione**: Lavorare sui draft con altri utenti

## 🛠️ Configurazione WordPress Reale

Per integrare con WordPress reale, aggiorna il file `.env` del backend:

```env
WORDPRESS_URL=http://your-wordpress-site.com
WORDPRESS_JWT_SECRET=your-super-secret-jwt-key-here
```

### Plugin WordPress Richiesti
1. **JWT Authentication for WP REST API**
2. **WP REST API** (già incluso in WordPress 4.7+)

### Configurazione JWT
Aggiungi al `wp-config.php`:
```php
define('JWT_AUTH_SECRET_KEY', 'your-super-secret-jwt-key-here');
define('JWT_AUTH_CORS_ENABLE', true);
```

## 🎯 Prossimi Passi

1. **Testare tutte le funzionalità** con gli utenti di test
2. **Configurare WordPress reale** se necessario
3. **Personalizzare i ruoli** e permessi
4. **Aggiungere più utenti di test** se necessario

## 🐛 Risoluzione Problemi

### Errore "Password required"
- ✅ **Risolto**: La colonna password è ora nullable per utenti WordPress

### Errore "User not found"
- Verifica che l'username sia corretto
- Controlla che l'utente esista nel sistema di test

### Errore "Invalid credentials"
- Verifica che la password sia corretta
- Controlla che l'utente sia configurato nel backend

## 📞 Supporto

Se riscontri problemi:
1. Controlla i log del backend: `storage/logs/laravel.log`
2. Verifica che entrambi i server siano in esecuzione
3. Testa l'API direttamente con curl
4. Controlla la console del browser per errori JavaScript



