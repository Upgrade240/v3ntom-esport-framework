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
5. **Commit**: `git commit -am 'Neue Funktion hinzugefügt'`
6. **Push**: `git push origin feature/neue-funktion`
7. **Pull Request** erstellen

## Development Setup

```bash
# Repository clonen
git clone https://github.com/DEIN-USERNAME/v3ntom-esport-framework.git
cd v3ntom-esport-framework

# Dependencies installieren
composer install
npm install

# Development Server starten
php -S localhost:8000

# Assets kompilieren
npm run build
```

## Code Standards

### PHP
- **PSR-12** Code Style
- **PHPDoc** für alle Funktionen
- **Type Hints** verwenden
- **Error Handling** mit Exceptions

### JavaScript
- **ES6+** Features verwenden
- **ESLint** Regeln befolgen
- **Kommentare** für komplexe Logic

### CSS
- **BEM** Methodology
- **CSS Variables** für Theming
- **Mobile-First** Approach

### Git Workflow
- **Feature Branches** für neue Features
- **Descriptive Commits** mit klaren Messages
- **Squash Commits** vor Merge
- **Tests** müssen bestehen

## Testing

```bash
# PHP Tests
composer test

# Code Style Check
composer lint

# Security Check
composer security
```

## Documentation

- **Code Comments** in Englisch
- **User Documentation** in Deutsch
- **API Documentation** mit Beispielen
- **Changelog** für alle Releases

## Release Process

1. **Version** in `package.json` und `composer.json` erhöhen
2. **CHANGELOG.md** aktualisieren
3. **Tests** ausführen
4. **Tag** erstellen: `git tag v1.0.0`
5. **Release** auf GitHub erstellen

## Support

Bei Fragen kannst du uns kontaktieren:
- **Discord**: [discord.gg/v3ntom](https://discord.gg/v3ntom)
- **Email**: dev@v3ntom.de
- **GitHub Issues**: für technische Fragen

Vielen Dank für deinen Beitrag! 🚀
