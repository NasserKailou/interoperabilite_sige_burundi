# SIGE Burundi — Système d'Interopérabilité
## Ministère de l'Éducation Nationale — République du Burundi

---

## 🌐 URLs d'accès

| Interface | URL locale | Description |
|-----------|-----------|-------------|
| **Portail public** | `http://localhost:3000/` | Tableau Kanban grand public |
| **Administration** | `http://localhost:3000/admin/` | Back-office AdminLTE |
| **Login admin** | `http://localhost:3000/admin/login.php` | Authentification |

### Accès démo administrateur
- **Email** : `admin@sige.bi`
- **Mot de passe** : `Admin2024!`

---

## 🚀 Installation rapide

### Prérequis
- PHP 7.3+ (testé avec 8.4)
- MariaDB / MySQL 5.7+
- Serveur Apache/Nginx **ou** PHP built-in server (développement)

### Étapes

```bash
# 1. Cloner/déposer le projet
cd /var/www/html/
# Déposer le dossier sige/

# 2. Configurer la base de données
sudo mysql < sige/sql/sige_schema.sql

# 3. Adapter la configuration
nano sige/includes/config.php
# → DB_HOST, DB_NAME, DB_USER, DB_PASS

# 4. Créer le mot de passe admin correct
php -r "echo password_hash('VotreMotDePasse', PASSWORD_BCRYPT);"
# → Mettre à jour sige_utilisateurs WHERE email='admin@sige.bi'

# 5. Démarrer (développement avec PHP built-in)
cd sige && php -S 0.0.0.0:3000 -t public router.php

# 6. Ou avec PM2 (sandbox / production légère)
pm2 start ecosystem.config.cjs
```

---

## 🏗️ Architecture du projet

```
sige/
├── public/               ← Portail grand public (racine web)
│   ├── index.php         ← Page d'accueil + Kanban
│   ├── api/              ← Endpoints AJAX (JSON)
│   │   ├── kpi.php       ← Indicateurs clés
│   │   ├── eleves.php    ← Données élèves StatEduc
│   │   ├── rh.php        ← Données SIGE-RH
│   │   ├── examens.php   ← Examens & concours
│   │   └── etablissements.php ← Carte scolaire
│   └── assets/           ← CSS, JS, images (copie)
│
├── admin/                ← Back-office AdminLTE
│   ├── login.php         ← Authentification
│   ├── dashboard.php     ← Tableau de bord consolidé
│   ├── connecteurs.php   ← Gestion connecteurs mock/API
│   ├── referentiels.php  ← Référentiels communs
│   ├── logs.php          ← Journal des échanges
│   ├── utilisateurs.php  ← Gestion des accès
│   ├── layout.php        ← En-tête AdminLTE (sidebar+navbar)
│   └── layout_end.php    ← Pied de page AdminLTE + scripts
│
├── includes/             ← Couche fonctionnelle PHP
│   ├── bootstrap.php     ← Point d'entrée commun (require en 1er)
│   ├── config.php        ← ★ CONFIGURATION CENTRALE ★
│   ├── database.php      ← Connexion PDO (Singleton)
│   ├── auth.php          ← Sessions, login, rôles
│   └── functions.php     ← Utilitaires (e(), fmt_number(), etc.)
│
├── connectors/           ← ★ COUCHE D'INTEROPÉRABILITÉ ★
│   ├── ConnectorInterface.php  ← Contrat commun
│   ├── MockConnector.php       ← Lecture JSON (mode mock)
│   ├── ApiConnector.php        ← Squelette prêt pour API réelles
│   └── ConnectorFactory.php    ← Sélecteur mock/api
│
├── mock_data/            ← Données de test JSON réalistes
│   ├── referentiels.json ← Provinces, communes, années...
│   ├── eleves.json       ← Effectifs StatEduc pluriannuels
│   ├── rh.json           ← Personnel SIGE-RH
│   ├── examens.json      ← Résultats CN8 & Examen d'État
│   └── etablissements.json ← Carte scolaire
│
├── assets/               ← Sources CSS/JS
│   ├── css/portal.css    ← Design portail public
│   └── js/portal.js      ← AJAX, Chart.js, interactions
│
├── sql/
│   └── sige_schema.sql   ← Schéma BDD + données initiales
│
├── logs/                 ← Journaux applicatifs (auto-créé)
├── router.php            ← Router PHP built-in server
└── ecosystem.config.cjs  ← Config PM2
```

---

## ⚙️ Configuration (includes/config.php)

### Basculement Mock ↔ API réelle

```php
// ★ LIGNE CLÉ : modifier 'mock' → 'api' pour activer les vraies API
define('DATA_SOURCE_MODE', 'mock');

// Renseigner les endpoints API réels
define('API_ENDPOINTS', [
    'statEduc'    => 'https://api.statEduc.bi/v1',      // ← à remplir
    'sige_rh'     => 'https://api.sige-rh.bi/v1',       // ← à remplir
    'examens'     => 'https://api.examens.bi/v1',        // ← à remplir
    'carte'       => 'https://api.carte-scolaire.bi/v1', // ← à remplir
    'referentiels'=> 'https://api.sige.bi/referentiels/v1', // ← à remplir
]);

// Tokens d'authentification API
define('API_TOKENS', [
    'statEduc' => 'TOKEN_ICI',  // ← à remplir
    'sige_rh'  => 'TOKEN_ICI',  // ← à remplir
    ...
]);
```

---

## 🔌 Brancher les API réelles — Guide pas-à-pas

### Étape 1 : Renseigner les endpoints
Dans `includes/config.php`, compléter `API_ENDPOINTS` et `API_TOKENS`.

### Étape 2 : Implémenter ApiConnector
Dans `connectors/ApiConnector.php`, chaque méthode contient un commentaire `// TODO` indiquant l'endpoint exact à appeler. La méthode `httpGet()` gère automatiquement le cURL, l'authentification Bearer et la décodification JSON.

### Étape 3 : Activer le mode API
```php
define('DATA_SOURCE_MODE', 'api');
```

### Étape 4 : Tester
- Via l'interface : `admin/connecteurs.php` → bouton "Tester"
- Via cURL : `curl -H "X-Requested-With: XMLHttpRequest" "http://localhost:3000/api/kpi.php?annee=14"`

### Garantie de transparence
Le reste du code (portail public, back-office, APIs AJAX) **ne sait pas** s'il utilise mock ou API réelle — tout passe par `ConnectorFactory::getConnector()`.

---

## 📊 Données disponibles (mode mock)

| Source | Fichier | Données |
|--------|---------|---------|
| StatEduc | `eleves.json` | Effectifs 2024→2029, 18 provinces, par niveau, nationalité |
| StatEduc | `etablissements.json` | 4 520 étab., infra, géolocalisation |
| SIGE-RH | `rh.json` | 78 500 personnels, 18 provinces, évolution |
| Examens | `examens.json` | CN8 + Examen d'État, historique 5 ans, par province |
| Référentiels | `referentiels.json` | 18 provinces, communes, secteurs, niveaux |

---

## 👥 Rôles et permissions

| Rôle | Portail | Admin | Connecteurs | Utilisateurs |
|------|---------|-------|-------------|--------------|
| Lecteur | ✅ | ✅ (lecture) | ❌ | ❌ |
| Éditeur | ✅ | ✅ | ❌ | ❌ |
| Admin | ✅ | ✅ | ✅ | ✅ |
| Super Admin | ✅ | ✅ | ✅ | ✅ + config API |

---

## 🎨 Design

- **Palette** : Bleu ciel `#1e88e5`, Blanc `#ffffff`, Vert `#43a047`, Rouge `#e53935`
- **Portail** : CSS vanilla responsive, Chart.js 4, FontAwesome 6
- **Admin** : AdminLTE 3.2 + Bootstrap 4.6

---

## ✅ Fonctionnalités livrées

- [x] Portail public — accueil Kanban avec 4 fenêtres
- [x] Portail — vue détaillée Élèves (graphiques + tableau)
- [x] Portail — vue détaillée RH (graphiques + tableau)
- [x] Portail — vue détaillée Examens & concours
- [x] Portail — vue détaillée Établissements + filtres
- [x] Sélecteur d'année global (filtre toutes les données)
- [x] KPI Band animé (compteurs)
- [x] APIs AJAX JSON (5 endpoints)
- [x] Back-office AdminLTE — login sécurisé (CSRF)
- [x] Back-office — tableau de bord consolidé
- [x] Back-office — gestionnaire de connecteurs
- [x] Back-office — référentiels communs (onglets)
- [x] Back-office — journal des échanges
- [x] Back-office — gestion utilisateurs (rôles)
- [x] Couche connecteur MockConnector (JSON)
- [x] Squelette ApiConnector (prêt à brancher)
- [x] ConnectorFactory (basculement transparent)
- [x] Données mock réalistes (5 fichiers JSON cohérents)
- [x] Script SQL complet (schéma + données initiales)
- [x] Design responsive (mobile, tablette, desktop)

## ✅ Extension v1.1.0 — Nouvelles fonctionnalités

### Carte scolaire Leaflet.js
- **`mock_data/coordonnees.json`** — 683 établissements avec coordonnées GPS réelles (source : Atlas Coline XLSX)
- **`public/api/carte.php`** — Endpoint GeoJSON + Stats + CSV (filtres : province, secteur, milieu, recherche libre)
- **Section "Carte scolaire"** dans le portail public avec Leaflet.js + MarkerCluster
  - Marqueurs colorés par secteur (Fondamental=bleu, Préscolaire=violet…)
  - Cercle/carré selon milieu urbain/rural
  - Popups enrichis (nom, province, commune, colline, statut, GPS)
  - Filtres combinables + compteur de résultats
  - Tableau paginé synchronisé avec la carte
  - Navigation depuis le tableau → zoom carte

### Pages admin détaillées
- **`admin/eleves.php`** — Évolution, répartition genre, par province + export CSV/Excel
- **`admin/rh.php`** — Ratio élèves/enseignant, % femmes, catégories + export
- **`admin/examens.php`** — Historique sessions, résultats CN8 par province + export
- **`admin/etablissements.php`** — Secteurs, milieu, infrastructures, GPS count + export

### Export CSV/Excel
- **`public/api/export.php`** — Endpoint unifié : `?module=eleves|rh|examens|etablissements|carte&format=csv|excel`
- BOM UTF-8 pour ouverture correcte dans Excel
- Séparateur `;` (standard FR)
- Disponible depuis chaque page admin via boutons CSV / Excel

## 📡 APIs disponibles

| Endpoint | Description |
|----------|-------------|
| `/api/kpi.php?annee=ID` | KPIs globaux |
| `/api/eleves.php?action=synthese|detail` | Données élèves |
| `/api/rh.php?action=synthese|detail` | Ressources humaines |
| `/api/examens.php?action=synthese|detail` | Examens & concours |
| `/api/etablissements.php?action=detail` | Établissements |
| `/api/carte.php?format=geojson|stats|csv` | Carte géolocalisée |
| `/api/export.php?module=X&format=csv|excel` | Export tabulaire |

## 🔜 Prochaines étapes suggérées

- [ ] Implémentation des méthodes ApiConnector.php (branchement API réelle)
- [ ] Notifications en temps réel (polling ou SSE)
- [ ] Mise en production Apache/Nginx + HTTPS + SSL
- [ ] Authentification JWT pour les APIs
- [ ] Tableau de bord analytique avancé (drill-down)
- [ ] Carte des contours de provinces (GeoJSON Burundi officiel)

---

**Version** : 1.1.0 | **PHP** : 8.4 | **MariaDB** : 11.8 | **Mode** : mock JSON | **GPS** : 683 étab. localisés
