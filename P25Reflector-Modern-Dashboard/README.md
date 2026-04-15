# DVSwitch Universal Reflector Dashboard (Modernized)

A high-performance, mode-agnostic real-time dashboard for **P25, YSF, and NXDN** reflectors. Optimized for the DVSwitch ecosystem.

## Key Features
- **Universal Mode Support**: Automatically detects and parses logs for P25, YSF, and NXDN.
- **Multi-Tenant Architecture**: Host multiple reflectors from a single codebase using auto-discovered configuration profiles.
- **Intelligent Bulk Setup**: Point the setup script at your `/opt` directory to automatically identify and configure all your reflectors at once.
- **Glassmorphism UI**: A premium, responsive design with "Command Center" aesthetics and live pulsating status indicators.
- **High Performance API**: Secure JSON-based log tailing without `exec()` or fragile shell commands.
- **Pretty URLs**: Support for clean paths (e.g., `https://domain.com/P25_NA`) via `.htaccess`.

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

3. **Configure Permissions**:
   Ensure the `config/` directory is writable by your web server if you want to use the setup script to save profiles.
   ```bash
   chmod -R 755 config/
   ```

## Usage

- **Main Dashboard**: Open your browser to the root directory. If you have multiple profiles, use the **Switch ▼** menu in the header.
- **Direct Access**: Access specific reflectors directly via `?conf=ProfileName` or via pretty URLs if using Apache: `https://yourdomain.com/ProfileName`.

## Requirements
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Access to the Reflector log files

## Credits
Project by **G4KLX**. Dashboard by **DVSwitch**. Modernized and Optimized for high-volume reflector systems.
