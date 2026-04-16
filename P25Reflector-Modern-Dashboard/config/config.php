<?php
/**
 * DVSwitch Universal Reflector Dashboard Configuration
 * Created via Interactive Setup on 2026-04-15 20:14:41
 */

date_default_timezone_set('UTC');

// --- 1. Branding & Identity ---
define("DASHBOARD_TITLE", "P25REFLECTOR Reflector");
define("DASHBOARD_SUBTITLE", "Real-time Network Monitoring");
define("LOGO", "DVSwitch.png");

// --- 2. Reflector Connection ---
define("REFLECTOR_LOG_PREFIX", "P25_NA_Reflector");
define("REFLECTOR_LOG_PATH", "/home/szingman/dvswitch_projects/P25Reflector/P25Reflector-Modern-Dashboard/logs");
define("REFLECTOR_INI_PATH", "/home/szingman/dvswitch_projects/P25Reflector/");
define("REFLECTOR_INI_FILE", "P25Reflector.ini");
define("REFLECTOR_BIN_PATH", "/home/szingman/dvswitch_projects/P25Reflector/");

// --- 3. UI Features ---
define("SHOWQRZ", "1");
define("SHOW_SYSTEM_STATS", "1");
define("SHOW_NETWORK_PULSE", "1");
define("GDPR_MODE", "0");  // Set to "1" to anonymize callsigns for GDPR compliance

// --- 4. Advanced Settings ---
define("API_REFRESH_INTERVAL", "2000");
define("LAST_HEARD_COUNT", "50");
define("TEMPERATUREHIGHLEVEL", "60");
?>