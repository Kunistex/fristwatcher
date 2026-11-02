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

## Deployment über GitHub Actions

Jeder Push auf `main` testet die App gegen MariaDB und deployt danach per rsync über SSH. Fehlen Secrets, wird nur getestet.

### 1. Secrets in GitHub

Repository → Settings → Secrets and variables → Actions → *New repository secret*:

| Secret | Inhalt |
| --- | --- |
| `SSH_HOST` | SSH-Server des Hostings, z.B. `sXX.metanet.ch` |
| `SSH_PORT` | SSH-Port, falls nicht 22 |
| `SSH_USER` | SSH-Benutzername |
| `SSH_PRIVATE_KEY` | Privater Schlüssel des Deploy-Keys (ganzer Inhalt inkl. `-----BEGIN…`) |
| `DEPLOY_PATH` | Absoluter Pfad zum App-Ordner auf dem Server, z.B. `/home/user/fristwatcher` |
| `PHP_BIN` | Optional: PHP-8.3-Binary, falls `php` auf dem Server eine ältere Version ist |
| `SSH_KNOWN_HOSTS` | Optional: Ausgabe von `ssh-keyscan -p PORT HOST` (sonst wird beim Deploy gescannt) |

Deploy-Key erzeugen (lokal):

```bash
ssh-keygen -t ed25519 -C "github-deploy-fristwatcher" -f fristwatcher_deploy -N ""
```

`fristwatcher_deploy.pub` im Hosting-Panel als autorisierten SSH-Schlüssel hinterlegen, `fristwatcher_deploy` (privat) als `SSH_PRIVATE_KEY` in GitHub.

### 2. Einmalig auf dem Server

1. MariaDB-Datenbank und Benutzer im Hosting-Panel anlegen.
2. Ordner `DEPLOY_PATH` anlegen und darin `.env` aus `.env.example` erstellen; DB-Zugang, Mail-Zugang und `APP_URL` eintragen.
3. Ersten Deploy laufen lassen (Push auf `main` oder Actions → *Run workflow*). Er schlägt mit «.env fehlt» fehl, falls Schritt 2 vergessen ging.
4. Per SSH im App-Ordner:
   ```bash
   php artisan key:generate
   php artisan webpush:vapid      # schreibt VAPID-Schlüssel in .env
   php artisan config:cache
   ```
5. Document Root der Domain auf `DEPLOY_PATH/public` setzen. Geht das nicht, leitet die `.htaccess` im Hauptordner auf `public/` um.
6. Cronjob im Panel, jede Minute:
   ```
   * * * * * cd DEPLOY_PATH && php artisan schedule:run >> /dev/null 2>&1
   ```
7. Seite öffnen: Das erste Konto wird über `/register` angelegt, danach ist die Registrierung geschlossen (`FRISTWATCHER_OPEN_REGISTRATION=true` öffnet sie).

Voraussetzungen beim Hosting: PHP 8.2+ (8.3 empfohlen) mit `pdo_mysql`, `mbstring`, `gd`, `intl`, `bcmath` oder `gmp`; SSH-Zugang; Cronjobs.

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
