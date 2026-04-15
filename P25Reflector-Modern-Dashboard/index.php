<?php
// Multi-Config discovery
$conf = isset($_GET['conf']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['conf']) : 'config';
$configFile = __DIR__ . "/config/{$conf}.php";

// Load the selected config
if (file_exists($configFile)) {
    include $configFile;
} else {
    $altConfigs = glob(__DIR__ . "/config/*.php");
    if (!empty($altConfigs)) {
        include $altConfigs[0];
        $conf = basename($altConfigs[0], '.php');
    } else {
        die("Setup required. Please run setup.php");
    }
}

// Map of all available reflectors for the switcher
$allConfigs = glob(__DIR__ . "/config/*.php");
$reflectors = [];
foreach ($allConfigs as $c) {
    $cName = basename($c, '.php');
    // Briefly include to get the Title without polluting global scope too much
    // (In a perfect world we'd parse the file, but this is PHP)
    $lines = file($c);
    $cTitle = $cName;
    foreach($lines as $line) {
        if (preg_match('/define\("DASHBOARD_TITLE",\s*"(.*?)"\)/', $line, $m)) {
            $cTitle = $m[1];
            break;
        }
    }
    $reflectors[] = ['id' => $cName, 'title' => $cTitle];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P25Reflector Dashboard | Modern</title>
    <link rel="stylesheet" href="index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Roboto+Mono:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo-section">
                <?php if (defined("LOGO") && LOGO != "" && LOGO != "none"): ?>
                    <img src="<?php echo LOGO; ?>" alt="DVSwitch Logo">
                <?php endif; ?>
                <div>
                    <div style="display: flex; align-items: center; gap: 1rem">
                        <h1 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em;">
                            <?php echo DASHBOARD_TITLE; ?>
                        </h1>
                        <?php if (count($reflectors) > 1): ?>
                        <div class="switcher-dropdown">
                            <button class="switcher-btn" style="background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 8px; color: var(--accent-color); padding: 5px 12px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
                                <?php echo $conf; ?> <span style="font-size: 0.6rem; margin-left: 4px;">▼</span>
                            </button>
                            <div class="switcher-content">
                                <div style="padding: 0.75rem 1rem; font-size: 0.7rem; color: #64748b; font-weight: 800; text-transform: uppercase;">Select Reflector</div>
                                <?php foreach ($reflectors as $r): ?>
                                    <a href="?conf=<?php echo $r['id']; ?>" class="<?php echo ($conf == $r['id']) ? 'active' : ''; ?>">
                                        <?php echo $r['title']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; font-weight: 500; opacity: 0.8;">
                        <?php echo DASHBOARD_SUBTITLE; ?>
                    </p>
                </div>
            </div>
            <div class="status-badge">
                <div id="mode-badge" class="badge">--</div>
                <div class="status-dot"></div>
                <span id="system-time">--:--:--</span>
            </div>
        </header>

        <main class="grid">
            <section class="main-content">
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-title">
                        <span>Currently Transmitting</span>
                        <span id="tx-status" class="badge">Idle</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Callsign</th>
                                <th>Target</th>
                                <th>Gateway</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody id="current-tx-body">
                            <tr><td colspan="4" style="text-align:center; color: var(--text-secondary)">Searching logs...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <div class="card-title">
                        <span>Last Heard</span>
                        <input type="text" id="lh-search" placeholder="Search..." style="font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; border: 1px solid var(--glass-border); background: rgba(0,0,0,0.2); color: var(--text-primary); width: 100px;">
                    </div>
                    <div style="max-height: 450px; overflow-y: auto; scrollbar-width: thin; border-radius: 8px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Time (UTC)</th>
                                    <th>Callsign</th>
                                    <th>Target</th>
                                    <th>Gateway</th>
                                    <th>Dur</th>
                                </tr>
                            </thead>
                            <tbody id="last-heard-body">
                                <!-- Populated via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if(defined("SHOW_NETWORK_PULSE") && SHOW_NETWORK_PULSE == "1"): ?>
                <div class="card" style="margin-top: 2rem">
                    <div class="card-title">Network Pulse</div>
                    <div id="event-log" style="font-size: 0.75rem; color: var(--text-secondary); max-height: 150px; overflow-y: auto;">
                        <!-- Populated via JS -->
                    </div>
                </div>
                <?php endif; ?>
            </section>

            <aside>
                <div class="card">
                    <div class="card-title">
                        <span>Gateways (<span id="gw-count">0</span>)</span>
                        <input type="text" id="gw-search" placeholder="Search..." style="font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; border: 1px solid var(--glass-border); background: rgba(0,0,0,0.2); color: var(--text-primary); width: 100px;">
                    </div>
                    <div style="max-height: 280px; overflow-y: auto; scrollbar-width: thin;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Callsign</th>
                                    <th>Last Seen</th>
                                </tr>
                            </thead>
                            <tbody id="gateways-body">
                                <!-- Populated via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php if(defined("SHOW_SYSTEM_STATS") && SHOW_SYSTEM_STATS == "1"): ?>
                <div class="card" style="margin-top: 2rem; background: rgba(56, 189, 248, 0.05)">
                    <div class="card-title" style="font-size: 0.875rem">System Stats</div>
                    <ul id="system-stats-list" style="list-style: none; font-size: 0.875rem; color: var(--text-secondary)">
                        <li id="row-port" style="display: none; justify-content: space-between; margin-bottom: 0.5rem">
                            <span>Network Port</span>
                            <span id="sys-port" style="color: var(--text-primary)">--</span>
                        </li>
                        <li id="row-temp" style="display: none; justify-content: space-between; margin-bottom: 0.5rem">
                            <span>CPU Temp</span>
                            <span id="sys-temp" style="color: var(--text-primary)">--</span>
                        </li>
                        <li id="row-load" style="display: none; justify-content: space-between; margin-bottom: 0.5rem">
                            <span>System Load</span>
                            <span id="sys-load" style="color: var(--text-primary)">--</span>
                        </li>
                        <li id="row-checkin" style="display: none; justify-content: space-between; margin-bottom: 0.5rem">
                            <span>DVRef Check-in</span>
                            <span id="sys-checkin" style="color: var(--text-primary)">--</span>
                        </li>
                        <li id="row-uptime" style="display: none; justify-content: space-between; margin-bottom: 0.5rem">
                            <span>Uptime</span>
                            <span id="sys-uptime" style="color: var(--text-primary)">--</span>
                        </li>
                    </ul>
                    <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 1rem 0">
                    <p style="font-size: 0.75rem; color: var(--text-secondary); text-align: center;">
                        Reflector by G4KLX<br>
                        Dashboard by DVSwitch<br>
                        <span style="opacity: 0.6">Inspired by the original dashboard project</span>
                    </p>
                </div>
                <?php endif; ?>
            </aside>
        </main>
    </div>

    <script>
        const SHOW_QRZ = <?php echo (defined("SHOWQRZ") && SHOWQRZ == "1") ? "true" : "false"; ?>;

        function formatCallsign(call, name, location) {
            let html = `<div class="callsign-container">`;
            if (SHOW_QRZ) {
                html += `<a href="https://qrz.com/db/${call}" target="_blank" class="callsign" style="text-decoration: none; color: var(--accent-primary)">${call}</a>`;
            } else {
                html += `<span class="callsign">${call}</span>`;
            }
            if (name) {
                html += `
                    <div class="callsign-tooltip">
                        <div style="font-weight: 800; color: #fff; margin-bottom: 2px;">${name}</div>
                        ${location ? `<div style="color: var(--accent-color); font-size: 0.65rem; font-weight: 600;">${location}</div>` : ''}
                    </div>
                `;
            }
            html += `</div>`;
            return html;
        }
        const CURRENT_CONF = "<?php echo $conf; ?>";

        async function updateDashboard() {
            try {
                const response = await fetch(`api.php?conf=${CURRENT_CONF}`);
                const data = await response.json();

                // Update Mode Badge
                const modeBadge = document.getElementById('mode-badge');
                if (data.mode) {
                    modeBadge.innerText = data.mode;
                    modeBadge.className = 'badge badge-' + data.mode.toLowerCase();
                }

                // Update System Time
                document.getElementById('system-time').innerText = data.timestamp;

                // Update System Stats
                const stats = {
                    'temp': data.system.temp,
                    'load': data.system.load,
                    'uptime': data.system.uptime,
                    'port': data.system.port,
                    'checkin': data.last_checkin ? data.last_checkin.split(' ')[1] : '--'
                };

                for (const [key, value] of Object.entries(stats)) {
                    const row = document.getElementById(`row-${key}`);
                    const valEl = document.getElementById(`sys-${key}`);
                    if (!row || !valEl) continue; // Skip if card is disabled
                    
                    if (value && value !== '--' && value !== '0h' && value !== '0.0°C') {
                        valEl.innerText = value;
                        row.style.display = 'flex';
                    } else {
                        row.style.display = 'none';
                    }
                }

                // Update Transmitting
                const txBody = document.getElementById('current-tx-body');
                const txStatus = document.getElementById('tx-status');
                
                if (data.transmitting) {
                    txStatus.innerText = 'TRANSMITTING';
                    txStatus.style.background = 'rgba(239, 68, 68, 0.2)';
                    txStatus.style.color = '#ef4444';
                    txBody.innerHTML = `
                        <tr class="tx-active-row">
                            <td>${formatCallsign(data.transmitting.callsign, data.transmitting.name, data.transmitting.location)}</td>
                            <td>${data.transmitting.target}</td>
                            <td>${data.transmitting.gateway}</td>
                            <td>Active</td>
                        </tr>
                    `;
                } else {
                    txStatus.innerText = 'IDLE';
                    txStatus.style.background = 'rgba(16, 185, 129, 0.2)';
                    txStatus.style.color = '#10b981';
                    txBody.innerHTML = `<tr><td colspan="4" style="text-align:center; color: var(--text-secondary)">No active transmission</td></tr>`;
                }

                // Update Last Heard
                const lhBody = document.getElementById('last-heard-body');
                const lhSearch = document.getElementById('lh-search').value.toUpperCase();

                lhBody.innerHTML = data.heard
                    .filter(item => 
                        item.callsign.toUpperCase().includes(lhSearch) || 
                        item.target.toUpperCase().includes(lhSearch) || 
                        item.gateway.toUpperCase().includes(lhSearch)
                    )
                    .map(item => `
                    <tr>
                        <td style="color: var(--text-secondary)">${item.time.split(' ')[1].substring(0, 8)}</td>
                        <td>${formatCallsign(item.callsign, item.name, item.location)}</td>
                        <td>${item.target}</td>
                        <td>${item.gateway}</td>
                        <td>${item.duration}</td>
                    </tr>
                `).join('');

                // Update Gateways
                const gwBody = document.getElementById('gateways-body');
                const gwSearch = document.getElementById('gw-search').value.toUpperCase();
                
                document.getElementById('gw-count').innerText = data.gateways.length;

                gwBody.innerHTML = data.gateways
                    .filter(gw => gw.callsign.toUpperCase().includes(gwSearch))
                    .map(gw => `
                        <tr>
                            <td>${formatCallsign(gw.callsign, gw.name)}</td>
                            <td style="color: var(--text-secondary); font-size: 0.75rem">${gw.last_seen.split(' ')[1]}</td>
                        </tr>
                    `).join('');

                // Update Events
                const eventLog = document.getElementById('event-log');
                if (eventLog) {
                    eventLog.innerHTML = data.events.map(ev => `
                        <div style="margin-bottom: 4px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 2px;">
                            <span style="color: var(--accent-primary)">${ev.time.split(' ')[1]}</span> ${ev.msg}
                        </div>
                    `).join('');
                }

            } catch (error) {
                console.error('Update failed:', error);
            }
        }

        const REFRESH_INTERVAL = <?php echo API_REFRESH_INTERVAL; ?>;

        // Initial update and periodic refresh
        updateDashboard();
        setInterval(updateDashboard, REFRESH_INTERVAL);
    </script>
</body>
</html>
