# DVSwitch Universal Reflector Dashboard (Modernized)

A high-performance, mode-agnostic real-time dashboard for **P25, YSF, and NXDN** reflectors. Optimized for the MMDVM ecosystem.

## Key Features
- **Universal Mode Support**: Automatically detects and parses logs for P25, YSF, and NXDN.
- **Multi-Tenant Architecture**: Host multiple reflectors from a single codebase using auto-discovered configuration profiles.
- **Enhanced User Identity (v3.1)**: Hover-activated tooltips showing Name and Location from a local high-speed SQLite database.
- **Intelligent Bulk Setup**: Point the setup script at your `/opt` directory to automatically identify and configure all your reflectors at once.
- **Glassmorphism UI**: A premium, responsive design with "Command Center" aesthetics and live pulsating status indicators.
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

1. **Clone the repository**:
   ```bash
   git clone https://github.com/DVSwitch/P25Reflector.git
   cd P25Reflector-Modern-Dashboard
   ```

2. **Run the Interactive Setup**:
   ```bash
   # Scan /opt for all reflectors
   php setup.php /opt
   ```

3. **Initialize the User ID Database**:
   Download the master user lists (DMR and NXDN) from RadioID.net:
   ```bash
   php update_db.php
   ```

4. **Configure Permissions**:
   ```bash
   chmod -R 755 config/
   chmod -R 777 db/
   ```

## Usage
- **Main Dashboard**: Open your browser to the root directory. Use the **Switch ▼** menu to toggle between reflectors.
- **User Discovery**: Hover your mouse over any callsign to see the user's name and location from the local ID database.
- **Pretty URLs**: Access directly via `https://yourdomain.com/ProfileName`.

## Credits
Reflector by **G4KLX**. Dashboard by **DVSwitch**. Modernized and Optimized for high-volume reflector systems.
