# Contributor Guide

## How to Contribute

1. Fork the repository (if using GitHub) or clone it directly.
2. Create a feature branch:
   ```bash
   git checkout -b feature/your-feature
   ```
3. Make your changes.
4. Run automated checks:
   ```powershell
   .\scripts\run_automated_checks.ps1
   ```
5. Commit with a descriptive message.
6. Push and create a pull request.

## Coding Standards

| Language | Standard |
|----------|----------|
| PHP | PSR-1 / PSR-12 (file-based includes; PSR-4 autoloading is not used) |
| Python | PEP 8 with 4-space indentation |
| HTML | Semantic HTML5 with proper ARIA labels |
| CSS | BEM-like naming convention; CSS variables for theming |
| SQL | Uppercase keywords, `snake_case` table and column names, prepared statements only |

## File and Folder Conventions

```
notun_alo/
├── *.php                     # PHP pages — one file per route
├── includes/                 # Shared PHP logic (helpers, config, partials)
├── ai-service/               # Python Flask microservices
├── database/                 # SQL dumps and migrations
├── cron/                     # Scheduled scripts
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── Phase 1 (RAG)/            # RAG knowledge base documents
├── tests/                    # Automated tests
└── docs/                     # Documentation
```

## Naming Conventions

| Context | Style | Examples |
|---------|-------|----------|
| PHP files | `snake_case` | `chatbot_api.php`, `init_db.php` |
| Database tables | `snake_case` | `user_ml_scores`, `assignment_scores` |
| Database columns | `snake_case` | `estimated_weight`, `schedule_date` |
| PHP functions | `camelCase` | `getCurrentUser()`, `startSession()` |
| PHP variables | `camelCase` | `$userId`, `$aiText` |
| Python identifiers | `snake_case` | `ingest_documents()`, `detect_language()` |
| CSS classes | `kebab-case` | `.btn-primary`, `.stat-card` |
| JavaScript functions | `camelCase` | `showToast()`, `fetchImpactData()` |

## Git Workflow

- **`main`** branch — production-ready (auto-deployed to Render).
- Feature branches: `feature/description`
- Bug fix branches: `fix/description`
- Commit messages: imperative mood, descriptive.
  ```
  Add circuit breaker to chatbot API
  Fix null-pointer in agency assignment
  ```
- Squash commits before merging to `main`.
- Tag releases with semantic versioning: `v1.0.0`, `v1.1.0`.

## Pull Request Guidelines

When opening a PR, include:

- A clear description of changes.
- Screenshots for UI changes.
- Test evidence for bug fixes.
- Links to related issues or feature requests.

### PR Checklist

- [ ] PHP lint passed (`php -l`)
- [ ] Tested locally
- [ ] No debug code or `var_dump()` statements
- [ ] Error handling added for all new endpoints
- [ ] Translations updated (if UI text changed)
