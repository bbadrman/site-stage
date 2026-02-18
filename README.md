# Stage Site — Symfony Project

Application Symfony 7.4 avec DDEV comme environnement de développement local.

## Prérequis

- **Docker Desktop** — [Installer Docker Desktop](https://www.docker.com/products/docker-desktop/)
- **DDEV** — [Installer DDEV](https://ddev.readthedocs.io/en/stable/users/install/)

### Installation de DDEV

**Windows (winget) :**
```powershell
winget install ddev
```

**Windows (Chocolatey) :**
```powershell
choco install -y ddev
```

**macOS (Homebrew) :**
```bash
brew install ddev/ddev/ddev
```

> **Note :** Après l'installation, redémarrez votre terminal pour que la commande `ddev` soit disponible.

## Installation du projet

1. **Cloner le dépôt :**
   ```bash
   git clone <url-du-depot>
   cd site-stage
   ```

2. **Démarrer DDEV :**
   ```bash
   ddev start
   ```

3. **Installer les dépendances PHP :**
   ```bash
   ddev composer install
   ```

4. **Configurer la base de données :**
   ```bash
   ddev exec php bin/console doctrine:migrations:migrate
   ```

## Accès

| Service       | URL                                    |
|---------------|----------------------------------------|
| **Site web**  | http://stage-site.ddev.site            |
| **Mailpit**   | http://stage-site.ddev.site:8025       |
| **Base de données** | `127.0.0.1` (port dynamique, voir `ddev describe`) |

### Identifiants base de données

| Paramètre | Valeur       |
|-----------|--------------|
| Type      | MariaDB 10.11 |
| Database  | `db`         |
| User      | `db`         |
| Password  | `db`         |
| Root pass | `root`       |

## Stack technique

- **PHP** 8.3
- **Symfony** 7.4
- **MariaDB** 10.11
- **Serveur web** : nginx-fpm
- **Composer** 2

## Commandes utiles

```bash
# Démarrer le projet
ddev start

# Arrêter le projet
ddev stop

# Accéder au conteneur web (SSH)
ddev ssh

# Exécuter une commande Symfony
ddev exec php bin/console <commande>

# Voir le statut du projet
ddev describe

# Voir les logs
ddev logs

# Relancer après modification de config DDEV
ddev restart

# Installer un package Composer
ddev composer require <package>
```

## Structure du projet

```
site-stage/
├── assets/          # Assets front-end (CSS, JS)
├── bin/             # Exécutables (console)
├── config/          # Configuration Symfony
├── migrations/      # Migrations Doctrine
├── public/          # Document root (index.php)
├── src/             # Code source PHP
├── templates/       # Templates Twig
├── tests/           # Tests PHPUnit
├── translations/    # Fichiers de traduction
├── .ddev/           # Configuration DDEV
├── composer.json    # Dépendances PHP
└── .env             # Variables d'environnement
```

## Améliorations récentes

### Authentification par Nom d'utilisateur (Username)
Le système d'authentification a été mis à jour pour permettre la connexion via **e-mail** ou **nom d'utilisateur**.
- **Entité User** : Ajout d'un champ `username` unique.
- **Inscription** : Le formulaire d'inscription inclut désormais un champ pour choisir un nom d'utilisateur.
- **Connexion** : Le champ identifiant accepte indifféremment l'e-mail ou le nom d'utilisateur.
- **Provider de sécurité** : Logiciel de recherche mis à jour dans `App\Security\UserProvider`.

## Licence

MIT
