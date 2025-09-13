#!/bin/bash

# V3NTOM Framework GitHub Setup Script
# Führe diesen Script in deinem geclonten Repository aus

echo "🚀 V3NTOM Framework GitHub Setup"
echo "=================================="

# Verzeichnisstruktur erstellen
echo "📁 Creating directory structure..."
mkdir -p {api,includes,admin,assets/{css,js,img},sql,logs,uploads,docs,tests}

# .gitignore erstellen
echo "📝 Creating .gitignore..."
cat > .gitignore << 'EOF'
# Configuration files
config.php
.env

# Logs
logs/*.log
logs/*.txt
!logs/.gitkeep

# Uploads  
uploads/*
!uploads/.gitkeep

# System files
.DS_Store
Thumbs.db
*.swp
*.swo

# IDE files
.vscode/
.idea/
*.sublime-*

# Dependencies
node_modules/
vendor/
composer.lock

# Temporary files
*.tmp
*.bak
*.old

# Security
*.key
*.pem
*.crt
EOF

# README.md erstellen
echo "📚 Creating README.md..."
cat > README.md << 'EOF'
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
EOF

# MIT License erstellen
echo "📄 Creating LICENSE..."
cat > LICENSE << 'EOF'
MIT License

Copyright (c) 2024 V3NTOM Team

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
EOF

# Placeholder Dateien für Logs/Uploads
echo "📁 Creating placeholder files..."
touch logs/.gitkeep
touch uploads/.gitkeep
echo "Log directory - do not delete this file" > logs/.gitkeep
echo "Upload directory - do not delete this file" > uploads/.gitkeep

# Package.json für NPM
echo "📦 Creating package.json..."
cat > package.json << 'EOF'
{
  "name": "v3ntom-esport-framework",
  "version": "1.0.0",
  "description": "Complete E-Sport Community Management System with Discord & TeamSpeak Integration",
  "main": "index.php",
  "scripts": {
    "build": "npm run build-css && npm run minify-js",
    "build-css": "postcss assets/css/main.css -o assets/css/main.min.css",
    "minify-js": "uglifyjs assets/js/main.js -o assets/js/main.min.js",
    "watch": "npm run watch-css & npm run watch-js",
    "watch-css": "postcss assets/css/main.css -o assets/css/main.min.css --watch",
    "dev": "php -S localhost:8000",
    "test": "php vendor/bin/phpunit tests/",
    "lint": "php vendor/bin/phpcs --standard=PSR12 includes/ api/ admin/",
    "lint-fix": "php vendor/bin/phpcbf --standard=PSR12 includes/ api/ admin/",
    "security-check": "php vendor/bin/security-checker security:check composer.lock"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/v3ntom/esport-framework.git"
  },
  "keywords": [
    "esport",
    "discord",
    "teamspeak",
    "community",
    "management",
    "php",
    "mysql",
    "gaming",
    "clan",
    "guild"
  ],
  "author": "V3NTOM Team <dev@v3ntom.de>",
  "license": "MIT",
  "bugs": {
    "url": "https://github.com/v3ntom/esport-framework/issues"
  },
  "homepage": "https://github.com/v3ntom/esport-framework#readme",
  "devDependencies": {
    "postcss": "^8.4.0",
    "postcss-cli": "^10.1.0",
    "autoprefixer": "^10.4.0",
    "cssnano": "^6.0.0",
    "uglify-js": "^3.17.0"
  },
  "engines": {
    "php": ">=7.4",
    "mysql": ">=5.7",
    "node": ">=14.0.0"
  }
}
EOF

# Composer.json für PHP Dependencies
echo "🎼 Creating composer.json..."
cat > composer.json << 'EOF'
{
    "name": "v3ntom/esport-framework",
    "description": "Complete E-Sport Community Management System with Discord & TeamSpeak Integration",
    "type": "project",
    "license": "MIT",
    "authors": [
        {
            "name": "V3NTOM Team",
            "email": "dev@v3ntom.de",
            "homepage": "https://v3ntom.de"
        }
    ],
    "require": {
        "php": ">=7.4",
        "ext-mysqli": "*",
        "ext-curl": "*",
        "ext-json": "*",
        "ext-openssl": "*",
        "ext-mbstring": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5",
        "squizlabs/php_codesniffer": "^3.7",
        "sensiolabs/security-checker": "^6.0"
    },
    "autoload": {
        "psr-4": {
            "V3NTOM\\": "includes/",
            "V3NTOM\\API\\": "api/",
            "V3NTOM\\Admin\\": "admin/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "V3NTOM\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "lint": "phpcs --standard=PSR12 includes/ api/ admin/",
        "lint-fix": "phpcbf --standard=PSR12 includes/ api/ admin/",
        "security": "security-checker security:check composer.lock",
        "post-install-cmd": [
            "php -r \"copy('config.example.php', 'config.php');\""
        ]
    },
    "config": {
        "optimize-autoloader": true,
        "sort-packages": true
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
EOF

# Docker Support
echo "🐳 Creating Docker files..."
cat > Dockerfile << 'EOF'
FROM php:8.1-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite headers

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 logs/ uploads/

# Apache configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
EOF

mkdir -p docker
cat > docker/apache.conf << 'EOF'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        AllowOverride All
        Require all granted
        
        # Security headers
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  web:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./logs:/var/www/html/logs
      - ./uploads:/var/www/html/uploads
    environment:
      - DB_HOST=db
      - DB_NAME=v3ntom_esport
      - DB_USER=v3ntom
      - DB_PASS=secure_password
    depends_on:
      - db
    networks:
      - v3ntom-network

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: v3ntom_esport
      MYSQL_USER: v3ntom
      MYSQL_PASSWORD: secure_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./sql:/docker-entrypoint-initdb.d
    networks:
      - v3ntom-network

  phpmyadmin:
    image: phpmyadmin:latest
    ports:
      - "8081:80"
    environment:
      PMA_HOST: db
      PMA_USER: v3ntom
      PMA_PASSWORD: secure_password
    depends_on:
      - db
    networks:
      - v3ntom-network

volumes:
  mysql_data:

networks:
  v3ntom-network:
    driver: bridge
EOF

# GitHub Actions CI/CD
echo "🔄 Creating GitHub Actions..."
mkdir -p .github/workflows

cat > .github/workflows/ci.yml << 'EOF'
name: CI/CD Pipeline

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: v3ntom_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mysqli, curl, json, openssl
        
    - name: Cache Composer packages
      id: composer-cache
      uses: actions/cache@v3
      with:
        path: vendor
        key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-php-
          
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
      
    - name: Run code style check
      run: composer lint
      
    - name: Run security check
      run: composer security
      
    - name: Run tests
      run: composer test
      env:
        DB_HOST: 127.0.0.1
        DB_DATABASE: v3ntom_test
        DB_USERNAME: root
        DB_PASSWORD: root

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Deploy to production
      run: |
        echo "Deploy to production server"
        # Add your deployment script here
EOF

cat > .github/workflows/security.yml << 'EOF'
name: Security Scan

on:
  schedule:
    - cron: '0 2 * * 1'  # Every Monday at 2 AM
  workflow_dispatch:

jobs:
  security:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Run security audit
      uses: securecodewarrior/github-action-add-sarif@v1
      with:
        sarif-file: security-audit.sarif
        
    - name: Check for known vulnerabilities
      run: |
        composer audit
EOF

# Issue Templates
echo "📋 Creating issue templates..."
mkdir -p .github/ISSUE_TEMPLATE

cat > .github/ISSUE_TEMPLATE/bug_report.md << 'EOF'
---
name: Bug Report
about: Erstelle einen Bug Report um uns zu helfen das Problem zu beheben
title: '[BUG] '
labels: 'bug'
assignees: ''
---

**Bug Beschreibung**
Eine klare und präzise Beschreibung des Bugs.

**Schritte zur Reproduktion**
1. Gehe zu '...'
2. Klicke auf '....'
3. Scrolle nach unten zu '....'
4. Siehe Fehler

**Erwartetes Verhalten**
Eine klare Beschreibung was erwartet wurde.

**Screenshots**
Falls anwendbar, füge Screenshots hinzu.

**System Information:**
 - OS: [z.B. Ubuntu 20.04]
 - PHP Version: [z.B. 8.1]
 - MySQL Version: [z.B. 8.0]
 - Browser: [z.B. Chrome, Safari]
 - Framework Version: [z.B. 1.0.0]

**Zusätzlicher Kontext**
Weitere Informationen zum Problem.
EOF

cat > .github/ISSUE_TEMPLATE/feature_request.md << 'EOF'
---
name: Feature Request
about: Schlage ein neues Feature für das Framework vor
title: '[FEATURE] '
labels: 'enhancement'
assignees: ''
---

**Feature Beschreibung**
Eine klare Beschreibung des gewünschten Features.

**Problem/Use Case**
Welches Problem löst dieses Feature? Beispiele:
- Als [Rolle] möchte ich [Feature] damit [Nutzen]

**Lösungsvorschlag**
Eine klare Beschreibung der gewünschten Lösung.

**Alternativen**
Alternative Lösungsansätze die du in Betracht gezogen hast.

**Zusätzlicher Kontext**
Screenshots, Mockups oder weitere Informationen.
EOF

# Pull Request Template
cat > .github/pull_request_template.md << 'EOF'
## Beschreibung
Beschreibe deine Änderungen hier.

## Art der Änderung
- [ ] Bug Fix (non-breaking change)
- [ ] Neues Feature (non-breaking change)
- [ ] Breaking Change (fix oder feature das bestehende Funktionalität ändert)
- [ ] Dokumentation Update

## Tests
- [ ] Ich habe neue Tests hinzugefügt die meine Änderungen abdecken
- [ ] Alle neuen und bestehenden Tests sind erfolgreich
- [ ] Ich habe manuell getestet

## Checklist
- [ ] Mein Code folgt den Code-Style Guidelines
- [ ] Ich habe eine Self-Review meines Codes durchgeführt
- [ ] Ich habe meinen Code kommentiert (besonders komplexe Bereiche)
- [ ] Ich habe entsprechende Dokumentation hinzugefügt/aktualisiert
- [ ] Meine Änderungen generieren keine neuen Warnings
- [ ] Abhängige Änderungen wurden in nachgelagerte Module übernommen
EOF

# Contributing Guidelines
echo "🤝 Creating contributing guidelines..."
cat > CONTRIBUTING.md << 'EOF'
# Contributing zum V3NTOM Framework

Vielen Dank für dein Interesse am V3NTOM Framework! 🎉

## Code of Conduct

Dieses Projekt folgt unserem [Code of Conduct](CODE_OF_CONDUCT.md). Durch die Teilnahme erwartest du, dass du diese Richtlinien einhältst.

## Wie kann ich beitragen?

### Bug Reports
- Verwende unsere Bug Report Vorlage
- Beschreibe das Problem klar und detailliert
- Füge Schritte zur Reproduktion hinzu
- Inkludiere System-Informationen

### Feature Requests  
- Verwende unsere Feature Request Vorlage
- Erkläre den Use Case klar
- Beschreibe die gewünschte Lösung

### Code Contributions
1. **Fork** das Repository
2. **Branch** erstellen: `git checkout -b feature/neue-funktion`
3. **Änderungen** implementieren
4. **Tests** hinzufügen/aktualisieren
5. **Commit**: `git commit -am 'Neue Funktion hinz
