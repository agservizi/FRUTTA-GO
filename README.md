# Frutta Go - Gestionale Mobile-First

Un gestionale web semplice e intuitivo per negozi di frutta e verdura, ottimizzato per dispositivi mobili.

## Caratteristiche

- **Mobile-first**: Design responsive ottimizzato per smartphone
- **Semplice**: Interfaccia intuitiva per utenti di tutte le età
- **Completo**: Gestione prodotti, magazzino, vendite e report
- **Sicuro**: Autenticazione, validazione e protezione CSRF
- **Scalabile**: Architettura MVC leggera con API REST

## Requisiti Server

- PHP 8.1+
- MySQL 5.7+
- Composer
- Node.js 16+ e npm
- Server web (Apache/Nginx) con mod_rewrite abilitato

## Installazione

1. **Clona o scarica il progetto**
   ```bash
   git clone https://github.com/your-repo/frutta-go.git
   cd frutta-go
   ```

2. **Installa dipendenze PHP**
   ```bash
   composer install
   ```

3. **Installa dipendenze Node.js**
   ```bash
   npm install
   ```

4. **Configura il database**
   - Crea un database MySQL
   - Copia `.env.example` in `.env` e configura le variabili
   - Esegui lo script SQL:
     ```bash
     mysql -u username -p database_name < database.sql
     ```
   - Oppure usa il setup automatico: vai su `http://tuosito/setup.php`

5. **Configura storage S3 (opzionale)**
   - Crea un bucket S3 o compatibile
   - Configura le variabili in `.env`

6. **Build assets**
   ```bash
   npm run build
   ```

7. **Avvia il server di sviluppo**
   ```bash
   npm run dev
   ```

## Configurazione

### Variabili d'ambiente (.env)

```env
# Database
DB_HOST=localhost
DB_NAME=frutta_go
DB_USER=your_db_user
DB_PASS=your_db_password

# Blob Storage (S3 compatible)
S3_ENDPOINT=https://s3.amazonaws.com
S3_BUCKET=your-bucket-name
S3_KEY=your_access_key
S3_SECRET=your_secret_key
S3_REGION=us-east-1

# App
APP_URL=http://localhost
DEBUG=false
```

### Permessi

- **Admin**: Accesso completo a tutte le funzionalità
- **Operatore**: Vendite, magazzino, prodotti (sola lettura), report

## Utilizzo

### Login
- Email: admin@fruttago.com
- Password: admin123

### Navigazione Mobile
- Bottom navigation con 4 sezioni principali
- Touch-friendly con pulsanti grandi (min 44px)

### Funzionalità Principali

#### Vendite
- Ricerca prodotti veloce
- Supporto kg/pezzi con calcolo automatico
- Sconti semplici (percentuale/valore fisso)
- Generazione ricevuta

#### Prodotti
- CRUD completo prodotti
- Categorie e unità di misura
- Upload immagini (S3)
- Preferiti

#### Magazzino
- Carico/scarico merci
- Tracking giacenze
- Storico movimenti

#### Report
- Giornaliero: incasso, numero vendite, top prodotti
- Mensile: totali, giorni migliori
- Export CSV

## Struttura Cartelle

```
/
├── public/                 # Document root
│   ├── index.php          # Entry point
│   ├── setup.php          # Setup iniziale
│   └── assets/            # Assets compilati
├── app/                   # Logica applicazione
│   ├── controllers/       # Controller
│   ├── services/          # Servizi business logic
│   ├── middleware/        # Middleware
│   ├── validators/        # Validatori
│   └── helpers.php        # Funzioni helper
├── templates/             # Template HTML
│   ├── pages/            # Pagine principali
│   └── partials/         # Componenti riutilizzabili
├── api/                  # Endpoint API
├── assets/               # Sorgenti frontend
│   ├── js/              # JavaScript
│   └── css/             # CSS
├── storage/              # File temporanei
│   ├── logs/            # Log errori
│   └── cache/           # Cache
├── config/               # Configurazioni
├── vendor/               # Dipendenze Composer
└── node_modules/         # Dipendenze npm
```

## Comandi Vite

```bash
# Sviluppo
npm run dev

# Build produzione
npm run build

# Preview build
npm run preview
```

## Sicurezza

- Password hash con `password_hash()`
- Protezione CSRF su form sensibili
- Prepared statements PDO
- Sanitizzazione output HTML
- Validazione input client e server
- Log errori sicuri

## API

### Autenticazione
```
POST /api?action=login
```

### Prodotti
```
GET  /api?action=products
POST /api?action=products
PUT  /api?action=products&id=123
DELETE /api?action=products&id=123
```

### Vendite
```
GET  /api?action=sales
POST /api?action=sales
```

### Magazzino
```
GET  /api?action=inventory
POST /api?action=inventory
```

### Report
```
GET /api?action=reports&type=daily|monthly
```

## Browser Supportati

- Chrome/Chromium 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Licenza

MIT License

## Supporto

Per supporto o segnalazioni bug, apri una issue su GitHub.