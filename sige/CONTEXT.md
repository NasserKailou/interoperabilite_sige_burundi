# CONTEXT.md — SIGE Burundi · Système d'Information pour la Gestion de l'Éducation

> **Fichier vivant** — mis à jour à chaque intervention. Dernière mise à jour : **2025-08-28**.

---

## 1. Vue d'ensemble du projet

| Élément | Valeur |
|---|---|
| **Nom complet** | Système d'Information pour la Gestion de l'Éducation (SIGE) — République du Burundi |
| **Ministère** | Ministère de l'Éducation Nationale du Burundi |
| **Objectif** | Plateforme d'interopérabilité consolidant les données de l'IUE, StatEduc, SIGE-RH, Examens et Carte scolaire |
| **Version** | Définie dans `includes/config.php` (`APP_VERSION`) |
| **Mode** | Démonstration (données mock) / Production (API réelles) selon `DATA_SOURCE_MODE` |
| **URL locale** | `http://localhost:8085/interoperabilite_sige_burundi/sige/` |
| **Dépôt GitHub** | `https://github.com/NasserKailou/interoperabilite_sige_burundi` |

---

## 2. Stack technique

| Couche | Technologie |
|---|---|
| **Backend** | PHP 8.2 / 8.4 natif (XAMPP Windows), sans framework |
| **Front admin** | AdminLTE 3.2 + Bootstrap 4.6 |
| **Front public** | HTML5 + CSS3 custom + JS vanilla |
| **Cartographie** | Leaflet.js 1.9.4 + MarkerCluster 1.5.3 |
| **Serveur web** | Apache (XAMPP) + mod_rewrite |
| **Base de données** | MySQL (XAMPP) — via `includes/db.php` |
| **Données mock** | Fichiers JSON dans `mock_data/` |

---

## 3. Architecture des fichiers

```
sige/
├── .htaccess                    # Routage racine : exclut /admin/ et /public/, redirige → public/
├── router.php                   # PHP built-in server router (sandbox PM2)
├── CONTEXT.md                   # CE FICHIER — documentation vivante
├── README.md                    # Readme général
│
├── includes/
│   ├── bootstrap.php            # Point d'entrée PHP (session, config, auth)
│   ├── config.php               # Constantes globales (BASE_URL, API_ENDPOINTS, API_TOKENS, APP_VERSION)
│   ├── auth.php                 # Auth::startSession(), requireLogin(), requireRole()
│   ├── functions.php            # Helpers globaux : e(), redirect(), json_response()
│   └── db.php                   # Connexion MySQL PDO
│
├── admin/
│   ├── .htaccess                # Options -Indexes, DirectoryIndex login.php, URLs propres
│   ├── layout.php               # Template AdminLTE (header, sidebar, nav)
│   ├── layout_end.php           # Fermeture du wrapper AdminLTE
│   ├── dashboard.php            # Tableau de bord admin — KPI, IUE banner, connecteurs
│   ├── eleves.php               # Gestion des élèves
│   ├── etablissements.php       # Gestion des établissements
│   ├── rh.php                   # Ressources humaines
│   ├── examens.php              # Examens nationaux
│   ├── connecteurs.php          # Connecteurs API (IUE en tête, flux IUE→StatEduc→SIGE)
│   ├── referentiels.php         # Référentiels (provinces, cycles, etc.)
│   ├── logs.php                 # Journal des activités
│   ├── utilisateurs.php         # Gestion des utilisateurs
│   ├── login.php                # Authentification admin
│   ├── logout.php               # Déconnexion
│   └── index.php                # Redirige → dashboard
│
├── public/
│   ├── .htaccess                # DirectoryIndex, URLs propres (X→X.php), fallback → index.php
│   ├── index.php                # Portail public — hero, stats, carte, systèmes connectés
│   └── api/
│       ├── boundary.php         # API GeoJSON : ?type=boundary (contour) | ?type=mask (voile)
│       ├── stats.php            # API statistiques globales
│       ├── eleves.php           # API données élèves
│       ├── etablissements.php   # API établissements
│       ├── provinces.php        # API provinces
│       └── ...
│
├── connectors/
│   ├── ConnectorFactory.php     # Factory : retourne MockConnector ou ApiConnector
│   ├── ConnectorInterface.php   # Interface commune
│   ├── MockConnector.php        # Données depuis mock_data/*.json
│   └── ApiConnector.php         # Appels API réelles (IUE, StatEduc, etc.)
│
├── mock_data/
│   ├── iue.json                 # Registre IUE : 2 578 000 élèves, 17 provinces, NID 90.8%
│   ├── burundi_boundary.geojson # Polygone officiel Burundi (361 points) pour Leaflet
│   ├── coordonnees.json         # 683 établissements géolocalisés (GPS)
│   ├── eleves.json              # Données élèves par année/province
│   ├── etablissements.json      # Liste établissements
│   └── ...
│
└── sql/
    └── schema.sql               # Schéma MySQL de la base de données
```

---

## 4. Systèmes interconnectés (IUE en source primaire)

```
IUE (Registre national)
  └─► StatEduc  (agrège, compile par année)
        └─► SIGE  (expose via API)
               ├─► Partenaires externes
               ├─► Carte scolaire
               └─► Examens / SIGE-RH
```

| Système | Rôle | Badge |
|---|---|---|
| **IUE** | Identification Unique des Élèves — registre national (2 578 000 élèves) | SOURCE PRIMAIRE |
| **StatEduc** | Recensement scolaire pluriannuel — agrège IUE | ACTIF |
| **SIGE-RH** | Gestion des ressources humaines | ACTIF |
| **Examens** | Concours nationaux & examen d'État | ACTIF |
| **Carte scolaire** | Géolocalisation de 683 établissements | ACTIF |

---

## 5. Authentification & Sécurité

- **Session** : `Auth::startSession()` dans `bootstrap.php`, nom `SIGE_SESS`
- **CSRF** : token dans `$_SESSION['csrf_token']`, vérifié sur POST
- **Rôles** : `superadmin` > `admin` > `lecteur` — contrôlés par `Auth::requireRole()`
- **Redirections** : relatives ou via `ADMIN_BASE_URL` (jamais de chemins absolus codés en dur)
- **Variables d'environnement** : `API_TOKENS` dans `config.php` (vides en mode mock)

---

## 6. URLs propres (mod_rewrite)

### `sige/.htaccess` (racine)
- Exclut `/public/` et `/admin/` des redirections
- Toute autre requête → `public/$1`

### `sige/public/.htaccess`
1. Fichiers/dossiers réels → servis directement
2. `X` → `X.php` si le fichier existe
3. Tout le reste → `index.php`

### `sige/admin/.htaccess`
- `Options -Indexes`
- `DirectoryIndex login.php`
- `X` → `X.php` si le fichier existe

### Convention dans le code PHP
- Tous les liens `href="X.php"` → `href="X"`
- Tous les `redirect('X.php')` → `redirect('X')`
- Formulaires : `action="login"` (sans `.php`)

---

## 7. Carte scolaire (Leaflet)

### Configuration
| Paramètre | Valeur |
|---|---|
| Centre | `[-3.38, 29.92]` (centre Burundi) |
| Zoom initial | 8 |
| minZoom | 7 (empêche de dézoomer sur les voisins) |
| maxBounds | `[[-4.47, 28.98], [-2.31, 30.85]]` + padding 0.25 |
| maxBoundsViscosity | 0.85 |

### Couches (basemaps)
| Bouton | Fournisseur | URL |
|---|---|---|
| **Clair** (défaut) | CartoDB Positron | `https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png` |
| OSM | OpenStreetMap | `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png` |
| Relief | OpenTopoMap | `https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png` |

### Masquage des pays voisins
- **API** : `GET /api/boundary.php?type=mask` → polygone inversé (monde entier - Burundi)
- **API** : `GET /api/boundary.php?type=boundary` → contour Burundi (liseré bleu #1565c0)
- **Source GeoJSON** : `mock_data/burundi_boundary.geojson` (361 points, source georgique/world-geojson)
- **Rendu** : voile gris `#e8eef4` fillOpacity 0.78 sur les pays voisins

### Marqueurs
- 683 établissements depuis `coordonnees.json`
- MarkerCluster actif, popups avec nom, province, code, GPS

---

## 8. Variables PHP globales (config.php)

| Constante | Usage |
|---|---|
| `ADMIN_BASE_URL` | URL base admin (ex: `http://localhost:8085/.../sige/admin`) |
| `PUBLIC_BASE_URL` | URL base public |
| `API_BASE_URL` | URL base API (`/api`) |
| `APP_VERSION` | Version de l'application |
| `DATA_SOURCE_MODE` | `'mock'` ou `'api'` |
| `SESSION_NAME` | `'SIGE_SESS'` |
| `API_ENDPOINTS['iue']` | Endpoint IUE (vide en mode mock) |
| `API_TOKENS['iue']` | Token IUE (vide en mode mock) |

### Variable JS injectée
```php
window.SIGE_BASE = '<?= API_BASE_URL ?>';
```

---

## 9. Historique des interventions

### Session 1 — Initialisation (date inconnue)
- Création de l'architecture complète SIGE
- Portail public (`public/index.php`) avec stats, carte, sections élèves/établissements
- Admin AdminLTE : dashboard, eleves, rh, examens, etablissements, connecteurs, referentiels, logs, utilisateurs
- Authentification CSRF, sessions PHP
- Données mock JSON (eleves, etablissements, provinces…)
- Carte Leaflet avec 683 établissements géolocalisés + MarkerCluster

### Session 2 — commit `6cde32a` (mise à jour config)
- Mise à jour `config.php`

### Session 3 — commit `914bc3b` / `38da37f` (fixes)
- Correction double `content-wrapper` admin
- Carte Leaflet agrandie (680px)
- Fix login CSRF + cartes kanban

### Session 4 — commit `77eaec9` (fixes)
- Login CSRF amélioré
- Cartes kanban fixes

### Session 5 — commit `67af8ec`
- **Fix `.htaccess` racine** : exclure `/admin/` de la redirection vers `public/`
- **Lien admin dynamique** : utilisation de `ADMIN_BASE_URL` au lieu de chemins relatifs cassés

### Session 6 — commit `829246f`
- **IUE comme source primaire** :
  - `mock_data/iue.json` créé (2 578 000 élèves, 17 provinces, NID 90.8%, flux, alertes)
  - `config.php` : endpoints et tokens IUE ajoutés
  - `admin/connecteurs.php` : IUE en première place avec flux visuel IUE→StatEduc→SIGE→Partenaires
  - `admin/dashboard.php` : bannière IUE, stats NID
  - `admin/eleves.php` : bannière source IUE
  - `admin/layout.php` : IUE dans sidebar (section INTEROPÉRABILITÉ)
- **Fix dashboard hauteur infinie** : suppression `layout-fixed`, CSS `height:auto !important`
- **Fix redirections admin** : `requireLogin()` et `requireRole()` utilisent des chemins relatifs/dynamiques

### Session 7 — **2025-08-28** (session courante)

#### Tâche 1 — Améliorations carte scolaire
- Basemap CartoDB Positron par défaut (plus professionnel qu'OSM)
- `minZoom:7` + `maxBounds` (Burundi étendu ×1.25) + `maxBoundsViscosity:0.85`
- Sélecteur de fond de carte (Clair / OSM / Relief) — boutons UI en haut à droite
- Hauteur carte : 680px → **820px**
- **Masque géographique** : `mock_data/burundi_boundary.geojson` (361 pts) + `public/api/boundary.php`
  - `?type=mask` → polygone inversé (voile gris sur pays voisins, fillOpacity 0.78)
  - `?type=boundary` → contour Burundi (liseré bleu #1565c0, weight 2.5)
- Contrôle d'échelle Leaflet ajouté

#### Tâche 2 — URLs propres (sans `.php`)
- `sige/admin/.htaccess` **créé** : `DirectoryIndex login.php`, `-Indexes`, règle `X→X.php`
- `sige/public/.htaccess` **modifié** : règle `X→X.php` avant fallback `index.php`
- Tous les `href="X.php"` → `href="X"` dans les 12 fichiers admin
- Tous les `redirect('X.php')` → `redirect('X')` dans login, logout, index, utilisateurs, auth
- `action="login.php"` → `action="login"` dans `login.php`

#### Tâche 3 — Section "Systèmes connectés" (page publique)
- `public/index.php` ligne ~319 : liste augmentée de 4 → **5 systèmes**
- **IUE ajouté en premier** avec paramètre `$isPrimaire = true`
- Badge vert "SOURCE PRIMAIRE" (gradient `#00e5b0→#00b88a`) sur la carte IUE
- Fond distinctif vert translucide + bordure pour IUE, badge étoilé "ACTIF ★"

#### Tâche 4 — Drapeau Burundi 🇧🇮
- Hero (`public/index.php` ligne ~95) : `<i class="fas fa-flag">` → `🇧🇮` (emoji, font-size 1.15rem)
- Footer (`public/index.php` ligne ~738) : `🇧🇮 République du Burundi` ajouté dans le copyright

#### Tâche 5 — CONTEXT.md
- Ce fichier créé à `sige/CONTEXT.md`

---

## 10. Tests effectués

| Test | Résultat |
|---|---|
| `php -l admin/dashboard.php` | ✅ No syntax errors |
| `php -l admin/connecteurs.php` | ✅ No syntax errors |
| `php -l admin/eleves.php` | ✅ No syntax errors |
| `php -l admin/layout.php` | ✅ No syntax errors |
| `php -l includes/auth.php` | ✅ No syntax errors |
| `php -l includes/config.php` | ✅ No syntax errors |
| `curl http://localhost:3000/` | ✅ HTTP 200 |
| `curl http://localhost:3000/admin/dashboard.php` | ✅ HTTP 302 (redirect auth) |
| Validation JSON `iue.json` (Python) | ✅ 2 578 000 élèves, 17 provinces, 3 alertes |

---

## 11. Points d'attention / dette technique

- `burundi_boundary.geojson` est servi depuis `mock_data/` via `boundary.php` — en production, envisager un cache HTTP
- `DATA_SOURCE_MODE = 'mock'` : toutes les données sont fictives, les `API_ENDPOINTS` sont vides
- Le mode API réel (`ApiConnector`) n'est pas testé (endpoints IUE non disponibles)
- MySQL non utilisé en mode mock — `db.php` jamais appelé avec `MockConnector`
- `window.SIGE_BASE` injecté par PHP pour les appels AJAX — vérifier la cohérence si le chemin de déploiement change

---

*Fin du fichier CONTEXT.md — mise à jour automatique à chaque session d'intervention.*
