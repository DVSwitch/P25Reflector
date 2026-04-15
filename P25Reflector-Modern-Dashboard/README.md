# DVSwitch Universal Reflector Dashboard (Modernized)

A high-performance, mode-agnostic real-time dashboard for **P25, YSF, and NXDN** reflectors. Optimized for the MMDVM ecosystem.

## Key Features
- **Universal Mode Support**: Automatically detects and parses logs for P25, YSF, and NXDN.
- **Multi-Tenant Architecture**: Host multiple reflectors from a single codebase using auto-discovered configuration profiles.
- **Intelligent Bulk Setup**: Point the setup script at your `/opt` directory to automatically identify and configure all your reflectors at once.
- **Glassmorphism UI**: A premium, responsive design with "Command Center" aesthetics and live pulsating status indicators.
- **High Performance API**: Secure JSON-based log tailing without `exec()` or fragile shell commands.
- **Pretty URLs**: Support for clean paths (e.g., `https://domain.com/P25_NA`) via `.htaccess`.

## System Requirements
To ensure all features (including the ID database and Pretty URLs) work correctly, the following PHP modules are required:
- `php-cli` (to run the setup and database updater)
- `php-sqlite3` (for high-speed user ID lookups)
- `php-curl` (to download the latest ID databases)
- `php-mbstring` (for international character support)

**Ubuntu/Debian Installation (PHP 8.3 example):**
```bash
sudo apt install php8.3-cli php8.3-sqlite3 php8.3-curl php8.3-mbstring
```

## Installation

1. **Clone the repository** to your web server:
   ```bash
   git clone https://github.com/DVSwitch/P25Reflector.git
   cd P25Reflector-Modern-Dashboard
   ```

2. **Run the Interactive Setup**:
   You can configure a single reflector or scan a directory for bulk setup.
   ```bash
   # Scan a directory for all reflectors (e.g., /opt)
   php setup.php /opt
   
   # Or point to a specific INI
   php setup.php /path/to/Reflector.ini
   ```

3. **Initialize the User ID Database**:
   Download the latest DMR and NXDN user lists from RadioID.net and populate your local cache:
   ```bash
   php update_db.php
   ```

4. **Configure Permissions**:
   Ensure the `config/` and `db/` directories are writable by your web server.
   ```bash
   chmod -R 755 config/
   chmod -R 777 db/
   ```

## Usage
- **Main Dashboard**: Open your browser to the root directory. If you have multiple profiles, use the **Switch ▼** menu in the header.
- **Direct Access**: Access specific reflectors directly via `?conf=ProfileName` or via pretty URLs if using Apache: `https://yourdomain.com/ProfileName`.

## Requirements
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Access to the Reflector log files

## Credits
Reflector by **G4KLX**. Dashboard by **DVSwitch**. Modernized and Optimized for high-volume reflector systems.
