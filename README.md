# Fristenwächter

Verträge, Abos und Ablaufdaten an einem Ort. Die App berechnet pro Vertrag den letzten Kündigungstag (Eingang beim Anbieter, mit Wochenenden und Feiertagen) und den empfohlenen Versandtag, und erinnert per E-Mail und Web-Push.

Laravel 12, MariaDB, Blade + Alpine.js, Tailwind CSS 4. Läuft auf Shared Hosting (Metanet, cyon) ohne Node, Redis oder Daemon auf dem Server.

## Funktionen

- Verträge mit Kosten, Laufzeit, Verlängerung und Kündigungsfrist; feste Termine wie 31.12. (Krankenkasse) inkl. eigener Frist pro Termin (`30.06.:3`)
- CH-Vorlagen: Grundversicherung, Franchise 300, Zusatzversicherung, Sach-/Haftpflicht (VVG), Fitness, Handy/Internet, Streaming, Jahresabo
- Live-Vorschau der Fristen im Formular
- Status-Ablauf: aktiv → Kündigung geplant → versendet (Bestätigung offen) → bestätigt → beendet
- Erinnerungen per E-Mail und Web-Push (PWA), Standard 30/7/0 Tage vor dem Versandtag
- Kündigungsschreiben als PDF (Fensterkuvert rechts, optional «Einschreiben»)
- Preisänderungen mit Sonderkündigungsrecht und Erinnerung
- Sparpotenzial: Kosten nach Kategorie, teuerste Verträge, ungenutzte Abos, Preiserhöhungen
- Ablaufdaten für Pass, ID, Führerausweis, Garantien, Vignette
- Dokumente pro Vertrag (Police, Bestätigung)

## Deployment über GitHub Actions (FTP)

Jeder Push auf `main` testet die App gegen MariaDB, baut sie (Composer ohne Dev-Pakete, Assets) und lädt sie per FTP hoch. Danach ruft der Workflow den abgesicherten Endpunkt `/_deploy` auf, der auf dem Server Migrationen, Vorlagen und Caches erledigt. Ein SSH-Zugang ist nicht nötig. Fehlen Secrets, wird nur getestet.

### 1. Secrets in GitHub

Repository → Settings → Secrets and variables → Actions:

| Secret | Inhalt |
| --- | --- |
| `FTP_SERVER` | FTP-Server, z.B. `ftp.example.ch` |
| `FTP_USERNAME` | FTP-Benutzer |
| `FTP_PASSWORD` | FTP-Passwort |
| `FTP_SERVER_DIR` | Ordner der App auf dem Server, **mit `/` am Ende**, z.B. `/fristwatcher/` |
| `DEPLOY_HOOK_TOKEN` | Zufallswert, mind. 32 Zeichen (`openssl rand -hex 32`). Gleicher Wert in der `.env` auf dem Server |
| `APP_URL` | Adresse der App, z.B. `https://fristen.example.ch` |
| `FTP_PROTOCOL` | Optional: `ftp`, falls der Server kein FTPS kann (Standard `ftps`) |

Ohne `DEPLOY_HOOK_TOKEN` und `APP_URL` läuft kein Deployment.

### 2. Einmalig auf dem Server

1. MariaDB-Datenbank und Benutzer im Hosting-Panel anlegen.
2. Per FTP im Ordner `FTP_SERVER_DIR` eine Datei `.env` anlegen (Vorlage: `.env.example`). Eintragen: `APP_URL`, `DB_*`, `MAIL_*` und `DEPLOY_HOOK_TOKEN`. `APP_KEY` und `VAPID_*` leer lassen, die erzeugt das erste Deployment selbst.
3. Document Root der Domain auf `FTP_SERVER_DIR/public` setzen. Geht das nicht, leitet die `.htaccess` im Hauptordner auf `public/` um und sperrt alles andere.
4. Push auf `main` oder Actions → *CI und Deployment* → *Run workflow*. Der erste Upload dauert länger (vendor-Ordner).
5. Cronjob im Panel, jede Minute. Mit PHP-Cron:
   ```
   cd FTP_SERVER_DIR && php artisan schedule:run
   ```
   Kennt das Hosting nur URL-Cronjobs:
   ```
   https://fristen.example.ch/_cron?token=DEPLOY_HOOK_TOKEN
   ```
6. `/register` öffnen und das erste Konto anlegen. Danach ist die Registrierung geschlossen (`FRISTWATCHER_OPEN_REGISTRATION=true` öffnet sie).

Voraussetzungen beim Hosting: PHP 8.2+ (8.3 empfohlen) mit `pdo_mysql`, `mbstring`, `gd`, `intl`, `bcmath` oder `gmp`; Cronjobs.

## Lokal entwickeln

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# DB_CONNECTION=sqlite für lokal genügt
php artisan migrate --seed
npm run dev & php artisan serve
php artisan test
```

## Befehle

| Befehl | Zweck |
| --- | --- |
| `fristwatcher:refresh` | Fristen neu berechnen, angekündigte Preise übernehmen, abgelaufene Kündigungen beenden (stündlich) |
| `fristwatcher:send-reminders` | Fällige Erinnerungen ab `FRISTWATCHER_REMINDER_HOUR` senden (stündlich) |
| `fristwatcher:create-user` | Konto auf der Kommandozeile anlegen |

## Fristlogik

`app/Services/DeadlineCalculator.php`:

1. Nächstes Laufzeitende, auf das noch fristgerecht gekündigt werden kann.
2. Letzter Eingangstag = Laufzeitende minus Frist; bei Monatsfristen auf Monatsende (30.6. minus 3 Monate = 31.3.). Fällt er auf Wochenende/Feiertag, gilt der Arbeitstag davor.
3. Empfohlener Versandtag = Eingangstag minus Postweg in Arbeitstagen (Standard 5).

Feiertage: `config/fristwatcher.php`. Vorlagen sind Startwerte; massgebend ist immer der eigene Vertrag.
