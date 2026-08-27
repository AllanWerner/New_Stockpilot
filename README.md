# StockPilot

Plateforme de gestion de stocks & commandes fournisseurs pour petits commerces multi-boutiques.
Projet fil rouge CDA 2026 — voir `Livrables_pdf/` (CDCF, modélisation BDD, architecture, méthodologie/UI-UX)
pour les documents de cadrage.

**Stack :** Symfony 7 (API REST) · Angular 17 (SPA) · PostgreSQL 16 · Docker · GitHub Actions.

État actuel : fondations complètes (BDD, Docker, CI, sécurité JWT) + modules **F1 — Comptes & boutiques**
et **F2 — Catalogue & scan code-barres**. F3/F4/F5 (commandes, tableaux de bord, notifications) suivront.

## Démarrage rapide (Docker)

Prérequis : Docker Desktop.

```bash
cp .env.example .env
# éditer .env si besoin (les valeurs par défaut conviennent pour du dev local)

docker compose build
docker compose up -d db php nginx

# première initialisation uniquement :
docker compose exec php bin/console lexik:jwt:generate-keypair
docker compose exec php bin/console doctrine:migrations:migrate -n
docker compose exec php bin/console doctrine:fixtures:load -n   # jeu de données de démo
```

L'application est servie sur `http://127.0.0.1` (ou le port défini par `HTTP_PORT` dans `.env` si 80 est déjà
pris sur la machine hôte — un service Windows préexistant l'occupe souvent).

**Comptes de démo** (créés par les fixtures) :
- Gérant : `gerant@stockpilot.test` / `password123`
- Employé : `employe@stockpilot.test` / `password123`

> **Note dev Windows :** la première requête après un démarrage à froid peut prendre plusieurs secondes
> (compilation du conteneur Symfony sur un volume bind Windows sans OPcache préchauffé). Les requêtes
> suivantes sont rapides. `docker/nginx/nginx.conf` prévoit une marge de 120s sur ce cas précis.

## Développement frontend rapide (sans rebuild Docker)

```bash
docker compose up -d db php   # backend seul
cd frontend
npm install
npm start   # ng serve, proxy /api vers localhost:$HTTP_PORT (lu depuis .env, voir proxy.conf.js)
```

## Tests

```bash
# Backend — unitaires + fonctionnels (PostgreSQL réel, rollback automatique par test via DAMA)
docker compose exec -e APP_ENV=test php bin/console doctrine:database:create --if-not-exists
docker compose exec -e APP_ENV=test php bin/console doctrine:migrations:migrate -n
docker compose exec -e APP_ENV=test php bin/phpunit

# Backend — analyse statique & style
docker compose exec php vendor/bin/phpstan analyse
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

# Frontend — unitaires, lint, build
cd frontend
npm test -- --watch=false --browsers=ChromeHeadlessCI
npm run lint
npm run build -- --configuration production
```

`-e APP_ENV=test` est nécessaire sur `docker compose exec` : le conteneur `php` a `APP_ENV=dev` comme
variable d'environnement réelle, qui prend le pas sur celle configurée par PHPUnit si elle n'est pas
explicitement surchargée à l'appel.

## Architecture

- **Backend** (`backend/`) : API REST Symfony pure (pas de rendu Twig), architecture en couches
  Controller → Service → Repository (interfaces) → Entity/Doctrine, JWT (LexikJWTAuthenticationBundle),
  CORS verrouillé (NelmioCorsBundle), autorisation via `AccessVoter`/`BoutiqueVoter`.
- **Frontend** (`frontend/`) : SPA Angular en composants standalone, Angular Material, charte graphique
  définie dans `src/styles/`.
- **Base de données** : schéma PostgreSQL 16 conforme à la modélisation Merise du Jalon 3
  (`backend/migrations/`), 12 tables/enums natifs.
- **Déploiement** : conteneurs `db` (Postgres), `php` (Symfony/PHP-FPM), `nginx` (reverse proxy + sert le
  build Angular). Seul le conteneur `php` accède à la base de données et à l'API externe Open Food Facts.

Voir `Livrables_pdf/Jalon4_Conception_Architecture_StockPilot.pdf` et
`diagrammes/classes_stockpilot_v5.puml` pour le détail de la conception.

## CI/CD

`.github/workflows/ci.yml` exécute à chaque push/PR : lint + analyse statique backend, tests PHPUnit
(PostgreSQL réel), lint/tests/build frontend, et une vérification `docker compose build`.

## Stratégie Git

Branches `main` (stable, tags de release) / `develop` (intégration) / `feature/F{n}-...` (par fonctionnalité),
merge via PR une fois la CI verte. Voir `Livrables_pdf/Methodologie_UIUX_StockPilot1.pdf` pour le détail des
conventions (commits, CI/CD, Kanban).
