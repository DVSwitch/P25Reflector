# P25Reflector Modern Dashboard (v2.0)

A ground-up rebuild of the P25Reflector interface, focused on performance, security, and modern aesthetics.

## Key Features
- **Glassmorphism UI**: High-end, translucent design with a responsive grid layout.
- **Real-Time Data**: 2-second refresh cycle using the modern **Fetch API** (replacing legacy AJAX).
- **Secure Architecture**: 100% PHP-native parsing; **zero** `exec()`, `shell_exec()`, or backtick dependencies.
- **Smart Log Engine**:
    - **Back-scanning Historical Search**: Deep-scans logs on startup to populate history.
    - **Multi-Log Support**: Automatically discovers and links historical rotation logs (e.g., `P25_NA-*.log`).
    - **Prefix-Aware**: Configuration-driven log targeting (supports multiple reflectors on one server).
- **Advanced Monitoring**:
    - **Network Pulse**: Live log of join/part/disappearance events.
    - **Transmission Metadata**: Automatically calculates duration by comparing start/end timestamps.
    - **Gateway Counter**: Real-time connected count prominently displayed.
    - **Searchable Lists**: Real-time filtering for both Gateways and Last Heard.
- **Operator Integration**: Optional **QRZ.com lookups** (all callsigns become clickable links).
- **Cloud/VM Optimized**: System stats (Temp, Load, Uptime) auto-detect availability and hide unusable fields.

## Configuration (`config/config.php`)
The dashboard is highly customizable with simple toggles:
- `DASHBOARD_TITLE` / `SUBTITLE`: Customize your branding.
- `LOGO`: Point to any image or set to `"none"` to hide.
- `SHOW_NETWORK_PULSE`: Toggle the event log.
- `SHOW_SYSTEM_STATS`: Toggle the hardware monitoring card.
- `SHOWQRZ`: Enable/Disable callsign linking.
- `API_REFRESH_INTERVAL`: Customize the polling rate (default 2000ms).

## Technical Requirements
- **PHP 7.4+** (Uses `SplFileObject` for performance).
- No external libraries (No jQuery, No Bootstrap) — keeping it lightweight and fast.
- Web server read access to P25Reflector log directory.
