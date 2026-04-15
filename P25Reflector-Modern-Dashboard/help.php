<?php
$title = "Dashboard Documentation";
$readmeContent = file_exists('README.md') ? file_get_contents('README.md') : '# Error\nREADME.md could not be found in the current directory.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help - P25Reflector</title>
    <link rel="stylesheet" href="index.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .markdown-body {
            color: #e2e8f0;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        .markdown-body h1, .markdown-body h2, .markdown-body h3 {
            color: var(--accent-primary);
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .markdown-body h1 {
            border-bottom: 1px solid var(--glass-border);
            padding-bottom: 0.5rem;
        }
        .markdown-body p, .markdown-body ul {
            margin-bottom: 1rem;
        }
        .markdown-body a {
            color: var(--accent-color);
            text-decoration: none;
        }
        .markdown-body pre {
            background: rgba(0, 0, 0, 0.4);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            overflow-x: auto;
            margin-bottom: 1rem;
        }
        .markdown-body code {
            background: rgba(0, 0, 0, 0.4);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: monospace;
            color: #38bdf8;
        }
        .markdown-body pre code {
            background: none;
            padding: 0;
            color: inherit;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 800px; margin: 0 auto; padding-top: 2rem;">
        <header style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <h1 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em;">
                    <?php echo $title; ?>
                </h1>
            </div>
            <a href="index.php" style="background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 8px; color: var(--accent-color); padding: 6px 16px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; transition: 0.2s;">
                Back to Dashboard
            </a>
        </header>

        <main>
            <div class="card" style="padding: 2.5rem;">
                <div id="content" class="markdown-body"></div>
            </div>
        </main>
    </div>

    <script>
        // Render the localized Markdown directly into the DOM
        document.getElementById('content').innerHTML = marked.parse(<?php echo json_encode($readmeContent); ?>);
    </script>
</body>
</html>
