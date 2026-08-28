<div align="center">

# StockPilot

**Gestion de stocks & commandes fournisseurs pour commerces multi-boutiques**

Projet fil rouge CDA 2026 — API REST Symfony + SPA Angular, conteneurisé, testé et livré en Git Flow.

[![CI](https://github.com/AllanWerner/New_Stockpilot/actions/workflows/ci.yml/badge.svg)](https://github.com/AllanWerner/New_Stockpilot/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/tag/AllanWerner/New_Stockpilot?label=release)](https://github.com/AllanWerner/New_Stockpilot/tags)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.3-000000?logo=symfony&logoColor=white)
![Angular](https://img.shields.io/badge/Angular-17.3-DD0031?logo=angular&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)

[Fonctionnalités](#-fonctionnalités) •
[Architecture](#-architecture) •
[Démarrage rapide](#-démarrage-rapide) •
[Structure du projet](#-structure-du-projet) •
[Intégration continue](#-intégration-continue) •
[Stratégie Git](#-stratégie-git--contribution)

</div>

---

## 📌 Aperçu

StockPilot est une plateforme de gestion de stocks pensée pour un petit réseau de boutiques : suivi du
catalogue produit boutique par boutique, commandes fournisseurs avec réception partielle, tableau de bord
de valorisation et notifications applicatives. Le backend expose une API REST pure (aucun rendu serveur),
consommée par une SPA Angular ; l'ensemble tourne en conteneurs Docker et est validé à chaque push par une
pipeline GitHub Actions à 6 jobs.

### En chiffres

| | |
|---|---|
| Fichiers source backend (PHP) | ~98 |
| Fichiers source frontend (TS + templates) | ~68 |
| Suites de tests backend (PHPUnit) | 9 classes (unitaires + fonctionnelles) |
| Suites de tests frontend (Karma/Jasmine) | 21 specs |
| Migrations Doctrine | 2 |
| Jobs CI par push/PR | 6 |
| Modules fonctionnels livrés | F1 → F5 |

## ✨ Fonctionnalités

<table>
<tr>
<td valign="top" width="33%">

**F1 — Comptes & boutiques**
- Authentification JWT
- Rôles compte `GERANT` / `EMPLOYE`
- Postes par boutique `RESPONSABLE` / `VENDEUR` (affectations)
- Création/désactivation employés & boutiques
- Gestion de compte (email, mot de passe)
- UI restreinte pour les vendeurs

</td>
<td valign="top" width="33%">

**F2 — Catalogue produits**
- CRUD produits, scan code-barres (Open Food Facts)
- Stock par boutique, sélecteur de boutique
- Catégories, filtres (catégorie/fournisseur/statut)
- Édition des prix réservée aux gérants
- Historique des mouvements (réception, transfert,
  ajustement) avec notification

</td>
<td valign="top" width="33%">

**F3 — Commandes fournisseurs**
- Création et envoi de commandes
- Réception totale ou partielle
- Statuts de commande (4 états)
- Vue consolidée multi-boutiques
- Date de livraison auto-calculée (délai fournisseur)

</td>
</tr>
<tr>
<td valign="top" width="33%">

**F4 — Tableau de bord**
- KPIs : valeur du stock, ruptures, seuils critiques,
  commandes en cours
- Courbe de valorisation sur 14 jours
- Table des produits sous seuil critique

</td>
<td valign="top" width="33%">

**F5 — Notifications**
- Historique (réception, seuil critique, ajustement)
- Marquer lu / non lu, individuellement ou en masse
- Filtre par plage de dates
- Badge de compteur non-lues dans la sidebar

</td>
<td valign="top" width="33%">

**Socle technique**
- Autorisation par voters (`AccessVoter`, `BoutiqueVoter`)
- CORS verrouillé, secrets via variables d'environnement
- Fixtures de démonstration réalistes (F1→F5)
- Pipeline CI : lint, analyse statique, tests, build

</td>
</tr>
</table>

## 🏗 Architecture

```
                    ┌──────────────────────────────┐
                    │        Angular 17 SPA          │
                    │   (composants standalone,      │
                    │    signals, Angular Material)   │
                    └───────────────┬─────────────────┘
                                    │ HTTP + JWT bearer
                                    ▼
                    ┌──────────────────────────────┐
                    │   nginx (reverse proxy)        │
                    │   sert le build Angular         │
                    │   + proxy /api → php-fpm        │
                    └───────────────┬─────────────────┘
                                    │ FastCGI
                                    ▼
                    ┌──────────────────────────────┐
                    │   Symfony 7 / PHP-FPM 8.4       │
                    │   Controller → Service →        │
                    │   Repository (interfaces) →     │
                    │   Entity / Doctrine ORM         │
                    │   JWT (Lexik) · voters d'autoris.│
                    └──────┬────────────────┬─────────┘
                           │                │
                           ▼                ▼
              ┌─────────────────────┐  ┌───────────────────────┐
              │  PostgreSQL 16        │  │  Open Food Facts API   │
              │  (enums natifs)        │  │  (lookup code-barres)  │
              └─────────────────────┘  └───────────────────────┘
```

Seul le conteneur `php` accède à la base de données et à l'API externe. `nginx` ne parle qu'à `php`
(FastCGI) et sert les fichiers statiques du build Angular ; il n'a aucun accès direct à la base.

### Tech stack

| Couche | Technologies |
|---|---|
| Frontend | Angular 17.3 · Angular Material/CDK 17.3 · RxJS 7.8 · TypeScript 5.4 · composants standalone + signals |
| Backend | Symfony 7.3 · Doctrine ORM 3.6 · LexikJWTAuthenticationBundle 3.2 · NelmioCorsBundle 2.6 · PHP 8.4 |
| Base de données | PostgreSQL 16 (types enum natifs) |
| Qualité | PHPStan 2.2 · PHP-CS-Fixer 3.95 · PHPUnit 12 · Angular ESLint · Karma/Jasmine |
| Infrastructure | Docker Compose · nginx 1.27 · GitHub Actions |
| API externe | Open Food Facts (lookup produit par code-barres) |

## 🚀 Démarrage rapide

**Prérequis :** Docker Desktop.

```bash
cp .env.example .env
# les valeurs par défaut conviennent pour du dev local

docker compose build
docker compose up -d db php nginx

# première initialisation uniquement :
docker compose exec php bin/console lexik:jwt:generate-keypair
docker compose exec php bin/console doctrine:migrations:migrate -n
docker compose exec php bin/console doctrine:fixtures:load -n   # jeu de données de démo
```

L'application est servie sur `http://127.0.0.1` (ou le port défini par `HTTP_PORT` dans `.env` si le port
80 est déjà occupé sur la machine hôte). Adminer (client BDD web) est disponible sur le port `ADMINER_PORT`
(8080 par défaut).

> **Note dev Windows :** la première requête après un démarrage à froid peut prendre plusieurs secondes
> (volume bind Windows sans OPcache préchauffé). Les requêtes suivantes sont rapides.

### Comptes de démonstration

Créés par les fixtures (`doctrine:fixtures:load`) — mot de passe `password123` pour tous :

| Email | Rôle compte | Poste / boutique |
|---|---|---|
| `gerant@stockpilot.test` | GERANT | — (accès à toutes les boutiques) |
| `responsable.centreville@stockpilot.test` | EMPLOYE | RESPONSABLE — Centre-ville |
| `vendeur.centreville@stockpilot.test` | EMPLOYE | VENDEUR — Centre-ville |
| `vendeur.rivegauche@stockpilot.test` | EMPLOYE | VENDEUR — Rive Gauche |

### Développement frontend rapide (sans rebuild Docker)

```bash
docker compose up -d db php   # backend seul
cd frontend
npm install
npm start   # ng serve, proxy /api vers localhost:$HTTP_PORT (voir proxy.conf.js)
```

### Tests

```bash
# Backend — unitaires + fonctionnels (PostgreSQL réel, rollback auto par test via DAMA)
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

> `-e APP_ENV=test` est nécessaire sur `docker compose exec` : le conteneur `php` a `APP_ENV=dev` comme
> variable d'environnement réelle, qui prime sur celle configurée par PHPUnit si elle n'est pas
> explicitement surchargée à l'appel.

## 📁 Structure du projet

```
StockpilotN/
├── backend/                     # API REST Symfony
│   ├── src/
│   │   ├── Controller/          # points d'entrée HTTP (JSON only)
│   │   ├── Service/              # logique métier
│   │   ├── Repository/           # accès aux données (interfaces + implém. Doctrine)
│   │   ├── Entity/                # entités Doctrine + enums natifs PostgreSQL
│   │   ├── Dto/{Request,Response} # objets de transfert (validation / sérialisation)
│   │   ├── Security/              # voters d'autorisation (AccessVoter, BoutiqueVoter)
│   │   ├── EventListener/         # écouteurs Symfony (ex. exceptions → JSON)
│   │   └── DataFixtures/          # jeu de données de démonstration
│   ├── tests/
│   │   ├── Functional/Controller/ # tests d'API bout-en-bout
│   │   └── Unit/Service/          # tests unitaires
│   └── migrations/                # migrations Doctrine
├── frontend/                    # SPA Angular
│   └── src/app/
│       ├── core/                  # auth, HTTP interceptors, layout, notifications, boutique courante
│       └── features/              # un dossier par module : auth, catalog, commande, compte,
│                                   # dashboard, mouvement-historique, notifications, organisation
├── docker/                      # Dockerfiles (nginx multi-stage build Angular + reverse proxy)
├── docker-compose.yml           # services db / php / nginx / adminer
├── .env.example                 # variables d'environnement documentées
└── .github/workflows/ci.yml     # pipeline CI (6 jobs)
```

## 🔄 Intégration continue

`.github/workflows/ci.yml` s'exécute à chaque push/PR sur `main` et `develop` :

| Job | Contenu |
|---|---|
| `backend-lint` | PHP-CS-Fixer (dry-run) + PHPStan |
| `backend-test` | PHPUnit sur PostgreSQL réel (service container) |
| `frontend-lint` | Angular ESLint |
| `frontend-test` | Karma/Jasmine (Chrome headless) |
| `frontend-build` | `ng build --configuration production` |
| `docker-build` | `docker compose build` (sanity-check de toutes les images) |

## 🌳 Stratégie Git & contribution

Git Flow simplifié :

- **`main`** — stable, tags de release (`vX.Y.Z`)
- **`develop`** — branche d'intégration, cible des PR de fonctionnalités
- **`feature/F{n}-nom-du-module`** — une branche par module (F1 à F5), créée depuis `develop`

Chaque fonctionnalité est développée sur sa branche, validée par la CI, puis fusionnée dans `develop` via
Pull Request. Les commits suivent l'esprit de [Conventional Commits](https://www.conventionalcommits.org/)
(`feat(F2): ...`, `fix: ...`, `chore: ...`) pour garder un historique lisible.

---

<div align="center">

Projet académique CDA 2026 — développé par [AllanWerner](https://github.com/AllanWerner)

</div>
