#!/bin/bash

# Script per configurare Supervisor per la coda Laravel
# Eseguire come root sul server remoto

echo "🚀 Configurazione Supervisor per Laravel Queue Worker..."

# 1. Installa Supervisor se non è già installato
if ! command -v supervisorctl &> /dev/null; then
    echo "📦 Installazione Supervisor..."
    apt update
    apt install -y supervisor
fi

# 2. Copia la configurazione
echo "📝 Copia configurazione Supervisor..."
cp supervisor-laravel-queue.conf /etc/supervisor/conf.d/laravel-queue-worker.conf

# 3. Crea la directory per i log se non esiste
echo "📁 Creazione directory log..."
mkdir -p /var/www/vhosts/api.giornaledelcilento.it/httpdocs/storage/logs
chown -R www-data:www-data /var/www/vhosts/api.giornaledelcilento.it/httpdocs/storage/logs

# 4. Rileggi la configurazione di Supervisor
echo "🔄 Rilettura configurazione Supervisor..."
supervisorctl reread

# 5. Aggiorna la configurazione
echo "⬆️ Aggiornamento configurazione Supervisor..."
supervisorctl update

# 6. Avvia i worker
echo "▶️ Avvio worker Laravel Queue..."
supervisorctl start laravel-queue-worker:*

# 7. Verifica lo stato
echo "✅ Stato worker:"
supervisorctl status laravel-queue-worker:*

# 8. Abilita Supervisor all'avvio
echo "🔧 Abilitazione Supervisor all'avvio..."
systemctl enable supervisor

echo "🎉 Configurazione completata!"
echo ""
echo "Comandi utili:"
echo "  supervisorctl status laravel-queue-worker:*  # Stato worker"
echo "  supervisorctl restart laravel-queue-worker:* # Riavvia worker"
echo "  supervisorctl stop laravel-queue-worker:*    # Ferma worker"
echo "  tail -f /var/www/vhosts/api.giornaledelcilento.it/httpdocs/storage/logs/queue-worker.log # Log worker"
