# Analisi nuova applicazione

## 1. Contesto e obiettivi
- Applicazione per giornale su WordPress che consente agli utenti già registrati sul sito (giornalisti/redattori) di accedere e produrre contenuti.
- Obiettivi principali:
  - Accelerare la creazione di articoli tramite IA a partire da titoli e descrizioni provenienti da feed RSS.
  - Permettere anche la redazione manuale di articoli con supporto IA per tag e ottimizzazione SEO.
  - Pubblicare articoli, immagini e tassonomie direttamente su WordPress via API, attribuendoli all’autore corretto.
  - Gestire più provider/modelli di IA con credenziali centralizzate, lasciando libertà di scelta all’utente.
- Ambito MVP:
  - Autenticazione degli utenti WordPress.
  - Gestione feed RSS (inserimento, eliminazione, disattivazione) e ingestione notizie.
  - Preferenze feed per utente: ogni giornalista seleziona quali feed visualizzare nella lista articoli.
  - Generazione articoli via IA (da titolo+descrizione) con creazione automatica dei tag.
  - Redazione manuale con supporto IA (tag/SEO), caricamento immagine, selezione categorie/sottocategorie, pubblicazione su WordPress.
  - Scelta del provider e del modello IA per ogni generazione.

## 2. Utenti target e bisogni
- Segmenti utente:
  - Giornalista/Redattore: crea articoli, sceglie provider/modello IA, pubblica a suo nome.
  - Editor/Caporedattore (facoltativo per MVP): supervisiona, gestisce fonti RSS, linee guida.
- Job-to-be-done principali:
  - Trasformare rapidamente segnali/notizie da RSS in articoli completi e coerenti con la linea editoriale.
  - Redigere manualmente articoli e usare IA per tag e SEO.
  - Pubblicare in modo semplice mantenendo categorie, sottocategorie e attribuzione autore su WordPress.

## 3. Value proposition
- Riduzione tempi di produzione e aumento throughput redazionale.
- Qualità omogenea grazie a prompt/linee guida e ottimizzazione SEO assistita.
- Integrazione diretta con WordPress (tassonomie, media, autori) e flessibilità multi-IA.

## 4. Requisiti funzionali
- MVP (must-have):
  - Login utente usando credenziali WordPress ed emissione sessione nell’app.
  - Gestione feed RSS: creare/aggiornare/eliminare/disattivare; deduplicazione item; stato lavorazione.
  - Preferenze feed per utente: attivazione/disattivazione feed da visualizzare nella lista degli articoli; filtro della lista articoli in base alle preferenze.
  - Creazione bozza da RSS: partendo da titolo+descrizione → invio a IA → generazione corpo articolo + tag; editing utente; upload immagine; scelta categoria/sottocategorie; pubblicazione su WordPress come autore loggato.
  - Redazione manuale: editor di testo; invio a IA per generazione tag e suggerimenti SEO; upload immagine; tassonomie da WordPress; pubblicazione a nome dell’utente.
  - Multi-IA: elenco provider e modelli configurati a livello di sistema; utente sceglie al momento della generazione; uso di credenziali condivise.
  - Sincronizzazione tassonomie (categorie/sottocategorie) e media con WordPress.
  - Permessi (ruoli):
    - Giornalista: può solo configurare quali feed visualizzare (non può inserire/eliminare feed/topic globali).
    - Editor/Admin: può gestire il catalogo feed/topic (crea/aggiorna/elimina/attiva-disattiva).
  - Concorrenza e collaborazione:
    - Claim sull’item RSS: un giornalista può “prendersi in carico” un item (claim) con scadenza automatica; visibilità in lista “in lavorazione da X”.
    - Lock bozza: quando si apre una bozza, si acquisisce un lock temporaneo rinnovato con heartbeat; editor/admin possono forzare unlock.
    - Versioning ottimistico: salvataggi delle bozze con controllo versione; in caso di conflitto, merge guidato e avviso.
    - Presenza in tempo reale: mostra utenti presenti nella stessa bozza.
- V1 (should-have):
  - Programmazione pubblicazione (schedule).
  - Preferenze utente predefinite (provider/modello IA preferito, categorie di default).
  - Mappatura feed→categoria predefinita; suggerimenti immagine; anteprima come su WordPress.
  - Versioning bozze e storico revisioni.
- Nice-to-have/futuro:
  - Workflow editoriale (bozza→review→pubblica), ruoli e permessi avanzati.
  - Moderazione automatica contenuti e controllo plagio.
  - Analitiche produzione (tempo medio, tasso pubblicazione, performance SEO).

## 5. Requisiti non funzionali
- Performance: UI reattiva; generazione IA asincrona con stato; p95 azioni critiche < 1s esclusi tempi IA.
- Scalabilità: polling/ingest RSS batch e code per generazioni IA; rate limiting verso provider IA e WordPress.
- Sicurezza: memorizzazione sicura chiavi IA; integrazione WordPress via token/app password; HTTPS; audit log azioni di pubblicazione.
- Compliance: GDPR (minimizzazione dati personali), log e conservazione conformi.
- Accessibilità: rispetto linee guida WCAG per l’editor.
- Localizzazione: UI in IT (estendibile EN).

## 6. Flussi principali (User Journeys)
- Login/autenticazione:
  - Utente inserisce credenziali WordPress → verifica con WordPress → sessione app.
- Gestione feed RSS (Editor/Admin):
  - Aggiunge URL feed, imposta stato attivo/disattivo; sistema importa item periodicamente; deduplica; permette marcatura degli item come "da lavorare", "in lavorazione", "completato".
- Configurazione feed personali (Giornalista):
  - Apertura schermata "I miei feed" → elenco feed disponibili definiti a livello globale → lo user abilita/disabilita solo la visualizzazione personale → la lista articoli mostra solo gli item provenienti dai feed abilitati per quell’utente. Nessuna possibilità di inserire/eliminare feed/topic.
- Claim item RSS:
  - Il giornalista apre la lista → esegue "Prendi in carico" su un item → l’item passa a stato "in lavorazione" con scadenza (es. 60 min, rinnovabile) → altri vedono chi lo sta lavorando.
- Editing bozza con lock/versioning:
  - Apertura bozza acquisisce lock; heartbeat ogni N secondi rinnova; il salvataggio invia `version` corrente; in conflitto, proposta di merge con differenze.
- Articolo da RSS con IA:
  - Selezione item RSS → precompilazione titolo/descrizione → scelta provider/modello IA → generazione corpo + tag → editing → upload immagine → selezione categorie/sottocategorie da WordPress → pubblica (crea post + media + tassonomie; autore = utente).
- Articolo manuale con supporto IA:
  - Redazione manuale → invio a IA per tag e suggerimenti SEO → eventuali correzioni → upload immagine → selezione categorie/sottocategorie → pubblica a nome dell’utente.

## 7. Architettura proposta
- Opzioni di stack
  - Opzione A — Laravel monolite (Blade/Livewire):
    - Frontend con Blade + Livewire 3 + Alpine.js + TailwindCSS.
    - Vantaggi: semplicità, meno complessità di deploy, time-to-market rapido.
  - Opzione B — Laravel API + Angular SPA:
    - Backend Laravel esposto come API REST (`/api/*`).
    - Frontend Angular 17 (Material) come SPA separata.
    - Vantaggi: UX più ricca, separazione netta, scalabilità client.
- Autenticazione utenti WordPress (comune ad A/B):
  - Preferenza MVP: plugin JWT su WordPress per scambio token; Laravel valida/gestisce sessione utente mappando `wpUserId`.
  - Alternative: OAuth2 (plugin WP OAuth Server) o App Passwords per Basic Auth su REST.
- Backend (Laravel 11):
  - Servizio ingest RSS con deduplica e normalizzazione item (Scheduler + code).
  - Endpoint autenticati per: preferenze feed utente, lista feed, lista articoli filtrata, generazione IA, publishing WP.
  - Concorrenza: storage lock/claim con TTL, heartbeat, override ruoli; versioning ottimistico su bozze.
  - Integrazione IA con pattern Strategy per provider/modello.
  - Client WordPress REST per post, media, tassonomie e autore.
- Realtime/presenza:
  - Laravel Echo/Broadcasting (Pusher/Ably/Redis websockets) o Livewire events per presenza/lock.
- Database e storage:
  - Tabelle: `users`, `feeds`, `feed_items`, `user_feed_preferences`, `drafts`, `providers`, `models`.
  - Estensioni per concorrenza: campi claim/lock e tabella `editing_sessions` (opzionale).
  - Storage immagini temporanee prima dell’upload via API WordPress.
- Messaggistica / job async:
  - Laravel Queue (Redis) + Horizon; Scheduler per import RSS e job IA.
- Osservabilità:
  - Log strutturati, metriche job, tracing base sulle integrazioni esterne.

## 8. Modello dati (alto livello)
- Utente (`users`): id, wpUserId, nome, email, ruoli.
- Feed (`feeds`): id, nome, url, stato (attivo/disattivo), fonte.
- Preferenze feed utente (`user_feed_preferences`): userId, feedId, isEnabled, createdAt, updatedAt.
- Item RSS (`feed_items`): id, feedId, guid/externalId, titolo, descrizione, url, stato lavorazione, claimedByUserId, claimedAt, claimExpiresAt, timestamps.
- Bozza (`drafts`): id, autoreUserId, sorgente (rss/manuale), titolo, contenuto, tag, categorie, immagine, stato, lockedByUserId, lockExpiresAt, version, updatedAt.
- Sessione editing (opz.) (`editing_sessions`): id, draftId, userId, heartbeatAt.
- Provider IA (`providers`, `models`): id, nome, tipo, modello, parametri default.

## 9. API (bozza)
- Feeds (Editor/Admin):
  - GET `/feeds` → elenco feed disponibili; include flag `isActive` globale; accesso lettura anche ai giornalisti.
  - POST `/feeds` → crea feed (solo Editor/Admin).
  - PUT `/feeds/{id}` → aggiorna feed (solo Editor/Admin).
  - DELETE `/feeds/{id}` → elimina feed (solo Editor/Admin).
- Preferenze feed personali (Giornalista):
  - GET `/me/feed-preferences` → elenco feed con `isEnabled` per l’utente.
  - PUT `/me/feed-preferences` body: `{ feedIdsEnabled: string[] }` → sostituisce preferenze dell’utente (non crea/elimina feed globali).
- Concorrenza item/bozze:
  - POST `/articles/{itemId}/claim` → assegna item all’utente con TTL; 409 se già assegnato; rinnova se proprietario.
  - DELETE `/articles/{itemId}/claim` → rilascia claim; Editor/Admin possono forzare.
  - POST `/drafts/{id}/lock` → acquisisce lock con TTL; rinnovo via heartbeat.
  - DELETE `/drafts/{id}/lock` → rilascia lock; override per Editor/Admin.
  - PATCH `/drafts/{id}` headers: `If-Match: <version>` → aggiorna contenuto; 409 su conflitto con payload differenze.
  - WS `/presence/drafts/{id}` → presenza utenti e stato lock in tempo reale.
- Articoli/Items:
  - GET `/articles` query: `feedIds[]` opzionale; di default applica preferenze utente per filtrare.
- IA:
  - POST `/ai/generate-from-rss` body: `{ itemId, providerId, modelId, promptOptions? }` → genera bozza + tag.
  - POST `/ai/seo-tags` body: `{ draftId|content, providerId, modelId }` → suggerisce tag/SEO per bozza manuale.
- WordPress publishing:
  - GET `/wordpress/taxonomies` → categorie/sottocategorie.
  - POST `/wordpress/publish` body: `{ draftId, image?, categories[], tags[] }` → crea post su WP a nome dell’utente, upload media, associa tassonomie.

## 10. Scelte tecnologiche
- Backend:
  - Laravel 11 (PHP 8.3), PostgreSQL 15/MySQL 8, Redis (cache/queue), Horizon.
  - HTTP client: Laravel HTTP (Guzzle) con retry/backoff.
  - RSS: `debril/feed-io` (o `simplepie/simplepie`).
  - WordPress: REST API (`/wp/v2/*`), autenticazione via JWT (MVP) o App Passwords.
  - IA: adapter per provider (OpenAI, Anthropic, Azure OpenAI, ecc.) con selezione modello runtime; chiavi in `.env`.
- Frontend (scegliere uno dei due):
  - A) Blade + Livewire 3, Alpine.js, TailwindCSS; editor: TinyMCE/CKEditor 5.
  - B) Angular 17, Angular Material, RxJS; editor: CKEditor 5/ngx-quill; i18n.
- Qualità:
  - Test: Pest/PHPUnit; ESLint/Prettier (se Angular); PHP-CS-Fixer; CI basica.
  - Security: secret management `.env`, CORS per SPA, rate limiting API.

## 11. Roadmap e milestone
- Fase 0: Discovery/PoC (1–2 settimane)
  - Decidere tra Opzione A (monolite) o B (API+SPA) e setup repo.
  - PoC autenticazione con WordPress (JWT) e mapping `wpUserId`.
  - PoC ingest RSS (1–2 feed), deduplica e listing item.
  - PoC IA: generazione da titolo+descrizione e creazione tag.
  - PoC publish: creare post bozza su ambiente di staging WP con media e tassonomie.
- Fase 1: MVP (3–5 settimane)
  - CRUD feed globale con ruoli (Editor/Admin) e import schedulato.
  - Preferenze feed personali (Giornalista) e filtro lista articoli.
  - Editor da RSS→IA: scelta provider/modello, generazione, editing, upload immagine, categorie/sottocategorie, pubblicazione a nome utente.
  - Editor manuale: testo, IA per tag/SEO, upload immagine, tassonomie, pubblicazione.
  - Multi-IA con adapter e configurazione centralizzata; code per job IA/publish.
  - Osservabilità base, gestione errori integrazioni, politiche retry.
  - (Se Opzione B) SPA Angular con viste: Login, Lista articoli, I miei feed, Editor RSS, Editor manuale.
- Fase 2: V1 (2–4 settimane)
  - Scheduling pubblicazioni; preferenze default (provider/modello, categorie).
  - Mappatura feed→categoria; anteprima stile WordPress; revisioni bozze.
  - Analitiche produzione e ottimizzazioni performance.

## 12. Rischi e mitigazioni
- Tecnici:
  - Integrazione WordPress (variazioni plugin REST/JWT, differenze versioni):
    - Mitigazione: ambiente di staging WP; fallback App Passwords; adapter client versionato; pin plugin e smoke test CI.
  - Limiti e rate limit API (WordPress, provider IA):
    - Mitigazione: queue con retry/backoff, circuit breaker, caching, budgeting chiamate per utente.
  - Qualità/eterogeneità feed RSS (formati, encoding, HTML sporco, duplicati):
    - Mitigazione: normalizzazione (Feed-IO), sanitizer HTML, dedup guid+hash contenuto, monitor stato feed.
  - Hallucination/bias dei modelli IA e incoerenza di stile:
    - Mitigazione: prompt con linee guida editoriali, temperature/tokens conservativi, post‑editing obbligatorio, checklist qualità; modelli multipli con fallback.
  - Costo e latenza IA:
    - Mitigazione: limiti input (riassunto/estrazione saliente), batch per tag, caching risultati ripetuti, modelli efficienti di default, job asincroni con progress UI.
  - Upload media (formati/size) e errori di rete:
    - Mitigazione: validazione e resize/compressione, upload idempotente con retry, verifica MIME.
  - Mappatura tassonomie con WordPress:
    - Mitigazione: sync iniziale+periodico, validazione categorie prima del publish, fallback a bozza se mismatch.
  - Consistenza e idempotenza pubblicazione:
    - Mitigazione: chiavi idempotenti per post/media, transazioni applicative, retry con DLQ e riconciliazione.
  - Sicurezza segreti provider IA:
    - Mitigazione: storage sicuro `.env`/secret manager, least privilege, rotate periodico, audit accessi.

- Prodotto/UX:
  - Sovraccarico nella scelta provider/modello per i giornalisti:
    - Mitigazione: default newsroom, preferiti utente, descrizioni/aiuto inline, ultimo modello usato.
  - Disallineamento contenuti IA con linea editoriale:
    - Mitigazione: policy di stile nei prompt, validazione umana prima del publish, training/onboarding.
  - Bassa adozione editor manuale:
    - Mitigazione: editor moderno (template, scorciatoie, autosave), performance alte, onboarding.

- Operativi/legali:
  - GDPR e trattamento dati personali (nei contenuti/log):
    - Mitigazione: minimizzazione, anonimizzazione log, DPA con provider IA, policy retention.
  - Copyright/licenze su testi e immagini:
    - Mitigazione: fonti autorizzate, controlli/avvisi diritti, libreria immagini con licenza, responsabilità autore esplicita.
  - Sicurezza accessi/ruoli:
    - Mitigazione: RBAC coerente con WordPress, log audit azioni critiche, 2FA su WP per ruoli elevati.
  - Continuità servizio (down di WP o dei provider IA):
    - Mitigazione: modalità degradata (salvataggio bozze locale), retry programmati, alerting, provider IA di backup.

## 13. KPI e metriche
- Attivazione/Adozione
  - Utenti attivi settimanali (WAU) e mensili (MAU)
  - Tasso attivazione nuovi giornalisti = utenti che completano primo publish / nuovi utenti
  - Retention D7/D30
- Throughput redazionale
  - Articoli pubblicati per giornalista per settimana
  - Lead time RSS→Publish (mediana/min, 90° percentile)
  - % articoli IA vs manuali; % bozze IA convertite in pubblicazioni
- Qualità IA/SEO
  - Revisioni per bozza IA (numero medio di edit significativi prima del publish)
  - Tasso rifiuto contenuti IA (% bozze scartate)
  - SEO score medio (Yoast/RankMath) e % articoli ≥ soglia target
- Affidabilità operativa
  - Publish success rate su WordPress ≥ target (errori/publish)
  - Job success rate (IA/publish) e tasso retry/DLQ
  - Freshness RSS: tempo medio ingest (feed→item disponibile), % feed con errori
- Performance (SLI)
  - p95 API chiave: `/articles`, `/ai/generate-from-rss`, `/wordpress/publish`
  - p95 generazione IA lato backend (escludendo tempo modello) e tempo end‑to‑end lato utente
- Costi
  - Costo IA per articolo = (token in/out × tariffa) / articoli
  - Spesa IA mensile vs budget, costo per redattore attivo
- Sicurezza/Compliance
  - Incidenti sicurezza (0 target), errori permessi RBAC, anomalie accesso
- Soddisfazione
  - NPS interno redazione, feedback qualitativo sull’editor e sulla qualità IA

- Target iniziali (MVP)
  - Publish success rate ≥ 99%
  - Lead time mediano RSS→Publish ≤ 30 min
  - p95 `/articles` ≤ 300 ms; p95 publish orchestrazione ≤ 3 s (escluso tempo IA)
  - SEO score medio ≥ “Good” per ≥ 80% articoli
  - Costo IA per articolo entro soglia definita (es. ≤ €0,15 per generazione base)

- Strumentazione e fonti dati
  - Backend: log strutturati Laravel, Horizon, metriche Prometheus/Grafana; tracing base integrazioni
  - Frontend (se SPA): Web Vitals opzionale, eventi custom (editor, publish)
  - WordPress: REST stats/plugin SEO per score; errori publish via callback
  - Data retention e anonimizzazione conformi GDPR

## 14. Stima effort (alto livello)
- Design/UX: [uomo-giorni]
- Frontend: [uomo-giorni]
- Backend: [uomo-giorni]
- QA/Release: [uomo-giorni]

## 15. Questioni aperte
- Scelta finale stack: A) Laravel monolite oppure B) Laravel API + Angular.
- Plugin WordPress per auth: JWT vs OAuth2/App Passwords.
- Editor: CKEditor 5 vs TinyMCE (e libreria su Angular se B).

---
Nota: questo documento è una bozza strutturata. Aggiorniamo le sezioni con i dettagli dell'analisi precedente non appena disponibili.
