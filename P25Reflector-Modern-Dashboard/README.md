# DVSwitch Universal Reflector Dashboard (v2.0)

A high-performance, responsive, and secure dashboard for P25, YSF, and NXDN reflectors. Rebuilt from the ground up for the modern digital voice enthusiast.

## Features
- **Universal Mode Support**: Automatically adapts to **P25**, **YSF**, and **NXDN** reflector logs.
- **Modern Glassmorphism UI**: Stunning, translucent design that is fully responsive for desktop and mobile.
- **Interactive Setup Wizard**: Includes `setup.php` to auto-detect settings from your reflector's INI file.
- **Real-Time Data**: High-speed updates via modern Fetch API (no page reloads required).
- **Secure by Design**: 100% PHP-native parsing; **zero** usage of `exec()` or other insecure shell commands.
- **Metadata Rich**:
    - **Duration Calculation**: Automatically calculates transmission times.
    - **Network Pulse**: Live log of join/part/disappearance events.
    - **Clickable Callsigns**: Optional QRZ.com integration for instant operator lookup.
    - **Global Gateway Counter**: Quick view of total connected nodes.

## Installation / Setup

1. **Clone the repository** to your web server.
2. **Run the Interactive Setup**:
   ```bash
   php setup.php /path/to/your/Reflector.ini
   ```
   The wizard will auto-detect your log paths and prefixes, and ask you for branding preferences (Title, Logo, etc.).

3. **Verify Configuration**: Check `config/config.php` if you need to make manual tweaks.

## Performance Requirements
- **PHP 7.4 or later** (uses `SplFileObject` for secure log reading).
- Web server read access to the reflector log directory.
- No external dependencies (lightweight Vanilla CSS/JS).

## Credits
- **Software**: P25Reflector by **G4KLX**.
- **Modernization**: Dashboard by **DVSwitch**.
- *Inspired by the original community dashboard project.*
