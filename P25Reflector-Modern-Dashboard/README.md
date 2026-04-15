# DVSwitch Universal Reflector Dashboard (Modernized)

A high-performance, mode-agnostic real-time dashboard for **P25, YSF, and NXDN** reflectors. Optimized for the MMDVM ecosystem.

## Key Features & Architecture (v3.2)
- **Universal Mode Support**: Automatically detects and parses logs for P25, YSF, and NXDN using a unified log ingestion engine.
- **Triple-Check Discovery Engine**: The `setup.php` wizard automatically identifies your infrastructure by scanning binaries, `.ini` headers, and filename patterns simultaneously. 
- **Multi-Tenant Switcher**: Host unlimited reflectors from a single codebase. Instantly swap dashboards using the global "Switcher" menu without reloading the page.
- **Tenant-Aware Telemetry**: The UI automatically surfaces the Active Profile and active network port.
- **Enhanced User Identity**: Hover-activated tooltips projecting from callsigns expose Name and Location from a high-speed SQLite database. Built with a global downward-drop physics engine to guarantee tooltips remain visible on all screen sizes without triggering horizontal scrollbars.
- **Glassmorphism UI**: A premium, responsive design with "Command Center" aesthetics and live pulsating status indicators.

## System Requirements
To ensure all features (including the ID database and Pretty URLs) work correctly, the following PHP modules are required:
- `php-cli` (to run the setup wizard and database updater)
- `php-sqlite3` (crucial for high-speed user ID lookups)
- `php-curl` (to download the latest ID databases)
- `php-mbstring` (for international character support)

**Ubuntu/Debian Installation Example:**  
*Note: Make sure to match the PHP version to your operating system's default (e.g., `php8.2-*` for Debian 12, or `php8.3-*` for newer Ubuntu systems).*
```bash
sudo apt install php8.2-sqlite3 php8.2-curl php8.2-mbstring
# After installing, make sure to restart your web server processor!
sudo systemctl restart php8.2-fpm
# OR
sudo systemctl restart apache2
```

## Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/DVSwitch/P25Reflector.git
   cd P25Reflector-Modern-Dashboard
   ```

2. **Run the Intelligent Setup Wizard**:
   Point the setup wizard to the directory containing your reflector `.ini` files (typically `/opt`).
   ```bash
   php setup.php /opt/P25Reflector
   ```

3. **Initialize the User ID Database**:
   Download the master user lists (DMR and NXDN) from RadioID.net. You must run this command for hover names to work!
   ```bash
   php update_db.php
   ```

4. **Configure Permissions**:
   ```bash
   chmod -R 755 config/
   chmod -R 777 db/
   ```

## Usage
- **Main Dashboard**: Open your browser to the root directory. Use the **Switch ▼** menu on the top right to hot-swap between multiple running reflectors.
- **User Discovery**: Hover your mouse over any callsign across any table (Last Heard, Gateways, Transmitting) to see the user's name and location from the local ID database.
- **Directory Access**: The API parses URL parameters natively. Access specific dashboards directly via `?conf=ProfileName`.

## Credits
Reflector by **G4KLX**. Dashboard by **DVSwitch**. Modernized and Optimized for high-volume reflector systems.
