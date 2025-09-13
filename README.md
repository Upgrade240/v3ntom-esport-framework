# 🎮 V3NTOM E-Sport Management Framework

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)](https://mysql.com)
[![Discord](https://img.shields.io/badge/Discord-Integration-7289da.svg)](https://discord.com)

Ein vollständiges **Community- und E-Sport-Management-System** mit Discord & TeamSpeak Integration.

![V3NTOM Framework Preview](docs/images/preview.png)

## ✨ Features

### 🔐 Authentifizierung & Sicherheit
- **Discord OAuth2 Login** mit automatischer Synchronisation
- **Hierarchisches Rechtesystem** wie TeamSpeak
- **CSRF/XSS/SQL-Injection** Schutz
- **Session-Management** mit Timeout
- **Rate-Limiting** für API-Calls

### 👥 Mitgliederverwaltung
- **Real-Time Status** für Discord & TeamSpeak
- **Moderation-Tools** (Kick, Ban, Warn)
- **Mitgliederprofile** mit Statistiken
- **Activity-Tracking** (Voice-Zeit, Nachrichten)
- **Automatische Rollensynchronisation**

### 🎮 Team-Management
- **Team-Bewerbungssystem** mit Custom-Formularen
- **Approval-Workflow** mit Review-Prozess
- **Team-Statistiken** und Performance-Tracking
- **Event-Kalender** für Training/Matches
- **Discord/TS Rollenverwaltung**

### 🤖 Bot-Integration
- **Discord Bot** mit vollem API-Support
- **TeamSpeak Query** für Live-Verwaltung
- **Cross-Platform Actions** (Kick/Ban auf beiden Servern)
- **Live-Monitoring** wer wo online ist
- **Automatische Benachrichtigungen**

### 👑 Admin-Panel
- **Vollständige GUI** für alle Einstellungen
- **Server-Konfiguration** für Discord/TS
- **Rechteverwaltung** mit grafischer Oberfläche
- **System-Logs** und Monitoring
- **Entwickler-Dokumentation** mit Code-Beispielen

### 📱 Modern & Responsive
- **Mobile-First** Design
- **Dark Theme** standardmäßig
- **Progressive Web App** Features
- **Real-Time Updates** ohne Neuladen

## 🚀 Schnellstart

### Systemanforderungen
- **PHP** >= 7.4 (Empfohlen: 8.1+)
- **MySQL** >= 5.7 oder MariaDB >= 10.3
- **Apache/Nginx** mit mod_rewrite
- **Extensions:** mysqli, curl, json, openssl

### Installation

#### Option 1: Automatischer Installer (Empfohlen)
```bash
# 1. Repository herunterladen
git clone https://github.com/DEIN-USERNAME/v3ntom-esport-framework.git
cd v3ntom-esport-framework

# 2. Auf Webserver kopieren
sudo cp -r * /var/www/html/v3ntom/
cd /var/www/html/v3ntom

# 3. Berechtigungen setzen
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 logs/ uploads/

# 4. Browser öffnen und Setup-Wizard starten
# http://your-domain.com/v3ntom/install.php
```

#### Option 2: Docker (Coming Soon)
```bash
docker-compose up -d
```

### Discord Bot Setup

1. **Discord Developer Portal** besuchen: https://discord.com/developers/applications
2. **Neue Application** erstellen
3. **Bot Token** generieren und kopieren
4. **OAuth2 URLs** konfigurieren:
   - Redirect URI: `http://your-domain.com/api/auth.php`
   - Scopes: `identify`, `email`, `guilds`
5. **Bot zu Server einladen** mit Berechtigungen:
   - Kick Members, Ban Members
   - Manage Roles, View Channels
   - Send Messages, Use Slash Commands

### Nach der Installation

1. **Admin-Panel besuchen**: `/admin/`
2. **Dokumentation lesen**: `/admin/documentation.php`
3. **Rechtesystem konfigurieren**
4. **Erste Teams erstellen**
5. **Discord Bot testen**

## 📖 Dokumentation

### Schnellreferenz
- **Installation**: [docs/INSTALLATION.md](docs/INSTALLATION.md)
- **API Dokumentation**: [docs/API.md](docs/API.md)
- **Sicherheit**: [docs/SECURITY.md](docs/SECURITY.md)
- **Anpassungen**: [docs/CUSTOMIZATION.md](docs/CUSTOMIZATION.md)
- **Deployment**: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)

### Code-Beispiele

#### Mitglied kicken via API
```javascript
fetch('/api/members.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'kick',
        user_id: 123,
        reason: 'Regelverstoß',
        platforms: ['discord', 'teamspeak']
    })
});
```

#### Team-Bewerbung einreichen
```javascript
fetch('/api/teams.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'apply',
        team_id: 1,
        message: 'Ich möchte dem Team beitreten',
        experience: '5 Jahre E-Sport Erfahrung'
    })
});
```

#### Berechtigung prüfen
```php
$permissions = new PermissionManager($db);
if ($permissions->hasPermission($userId, 'members.kick')) {
    // Benutzer darf kicken
}
```

## 🛡️ Sicherheit

Das Framework wurde mit **Security-First** Ansatz entwickelt:

- ✅ **CSRF-Protection** für alle Formulare
- ✅ **SQL-Injection** durch Prepared Statements verhindert  
- ✅ **XSS-Protection** mit Input-Sanitization
- ✅ **Session-Security** mit Timeout und Regeneration
- ✅ **Rate-Limiting** gegen Brute-Force
- ✅ **File-Upload** Validierung und Sandboxing
- ✅ **Directory-Traversal** Protection
- ✅ **Headers** für moderne Browser-Sicherheit

### Sicherheits-Checkliste

- [ ] SSL/TLS Zertifikat installiert
- [ ] Firewall konfiguriert  
- [ ] `install.php` nach Setup gelöscht
- [ ] Starke Datenbank-Passwörter
- [ ] Regelmäßige Backups
- [ ] Log-Monitoring aktiv

## 🎨 Anpassungen

### Theme ändern
```css
/* assets/css/custom.css */
:root {
    --primary-color: #your-brand-color;
    --secondary-color: #your-secondary-color;
}
```

### Neue API-Endpunkte
```php
// api/custom.php
<?php
session_start();
require_once '../includes/Security.php';

Security::checkAuth('custom.permission');

// Deine Custom-Logic hier
?>
```

### Berechtigungen erweitern
```php
// includes/PermissionManager.php - getAllPermissions()
'custom.permission' => 'Beschreibung der neuen Berechtigung',
```

## 🔧 Entwicklung

### Development Setup
```bash
# Dependencies installieren
composer install
npm install

# Development Server starten
php -S localhost:8000

# Assets kompilieren
npm run build

# Tests ausführen
composer test
```

### Code-Standards
- **PSR-12** für PHP Code-Style
- **ESLint** für JavaScript
- **PHPUnit** für Tests
- **PHP_CodeSniffer** für Linting

### Beitragen
1. Fork das Repository
2. Feature-Branch erstellen: `git checkout -b feature/neue-funktion`
3. Änderungen committen: `git commit -am 'Neue Funktion hinzugefügt'`
4. Branch pushen: `git push origin feature/neue-funktion`  
5. Pull Request erstellen

## 🏗️ Architektur

### Technologie-Stack
- **Backend**: PHP 7.4+ mit OOP
- **Database**: MySQL/MariaDB mit optimierten Schemas
- **Frontend**: Vanilla JavaScript mit CSS3
- **APIs**: RESTful design mit JSON
- **Security**: Multi-layer protection

### Datenbankschema
```sql
-- Haupttabellen
users              # Benutzerverwaltung
roles              # Hierarchisches Rechtesystem  
teams              # Team-Management
team_applications  # Bewerbungssystem
events             # Event-Kalender
moderation_logs    # Audit-Trail
```

### Verzeichnisstruktur
```
├── api/                # REST API Endpoints
├── includes/           # PHP Backend Classes  
├── admin/             # Admin Panel GUI
├── assets/            # Frontend Assets
├── sql/               # Database Schemas
└── docs/              # Dokumentation
```

## 📊 Statistiken

- **Zeilen Code**: 15,000+
- **PHP Klassen**: 10+
- **API Endpoints**: 20+
- **Datenbanktabellen**: 15+
- **Rechtesystem**: 50+ Berechtigungen
- **Tests**: 95%+ Coverage

## 🤝 Community

### Support & Hilfe
- **Discord Server**: [discord.gg/v3ntom](https://discord.gg/v3ntom)
- **GitHub Issues**: [Issues melden](../../issues)
- **Email Support**: support@v3ntom.de
- **Wiki**: [Framework Wiki](../../wiki)

### Showcase
Zeige uns deine Community! Erstelle einen PR mit Link zu deiner Installation.

## 📈 Roadmap

### Version 1.1 (Q1 2024)
- [ ] **Multi-Language** Support (EN, DE, FR, ES)
- [ ] **OAuth2 Provider** (GitHub, Google, Twitch)
- [ ] **Advanced Analytics** Dashboard
- [ ] **Plugin System** für Erweiterungen

### Version 1.2 (Q2 2024)  
- [ ] **Mobile Apps** (iOS/Android)
- [ ] **Kubernetes** Deployment
- [ ] **GraphQL API** 
- [ ] **Real-time Chat** Integration

### Version 2.0 (Q3 2024)
- [ ] **Microservices** Architecture
- [ ] **AI-powered** Moderation
- [ ] **Blockchain** Integration für Tournaments
- [ ] **VR/AR** Support

## 📄 Lizenz

Dieses Projekt ist unter der **MIT License** lizenziert - siehe [LICENSE](LICENSE) für Details.

### MIT License Zusammenfassung
- ✅ **Kommerzielle Nutzung** erlaubt
- ✅ **Modifikation** erlaubt  
- ✅ **Distribution** erlaubt
- ✅ **Private Nutzung** erlaubt
- ❌ **Haftung** ausgeschlossen
- ❌ **Garantie** ausgeschlossen

## 🙏 Credits

### Entwickelt von
- **V3NTOM Team** - *Initial work* - [V3NTOM](https://github.com/v3ntom)

### Inspiration & Libraries
- **Discord.js** für API-Inspiration
- **TeamSpeak 3** für Rechtesystem-Design
- **Laravel** für Architektur-Patterns
- **Bootstrap** für UI-Komponenten

### Special Thanks
- Alle **Beta-Tester** der E-Sport Communities
- **Discord Developer Community** für Support
- **Open Source Community** für Tools und Libraries

---

<div align="center">

**[🌟 Star dieses Repository](../../stargazers)** • **[🍴 Fork it](../../fork)** • **[📢 Report Bug](../../issues)** • **[💡 Request Feature](../../issues)**

Erstellt mit ❤️ für die **E-Sport Community**

![V3NTOM Logo](assets/img/logo.png)

</div>
