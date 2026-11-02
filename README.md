# policenbetreuung.ch

Website mit Formularen (PHP für den Mailversand). Jeder Push auf `main` lädt die Seite automatisch zu Infomaniak.

## Einmalig einrichten
1. Infomaniak-Manager > Web-Hosting > FTP/SSH-Benutzer anlegen (Zugriff auf den Ordner der Domain).
2. GitHub > Settings > Secrets and variables > Actions > New repository secret:
   - `FTP_SERVER` (z. B. xxxx.ftp.infomaniak.com)
   - `FTP_USERNAME`
   - `FTP_PASSWORD`
   - `FTP_DIR` (Zielordner, z. B. /sites/policenbetreuung.ch/)
3. Postfach anfrage@policenbetreuung.ch anlegen, SSL für die Domain aktivieren.
4. Actions > "Deploy zu Infomaniak" > Run workflow.
