<?php
/**
 * PWA Diagnostic Test Page
 * Use this to check if sw.js and manifest.json are accessible
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWA Diagnostic Test - SVMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 20px;
            background: #f5f5f5;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #130117; margin-bottom: 20px; }
        h2 { color: #2e1731; margin: 25px 0 15px; font-size: 18px; }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #ccc;
        }
        .test-item.success { background: #d1fae5; border-color: #10b981; }
        .test-item.error { background: #fee2e2; border-color: #ef4444; }
        .test-item.warning { background: #fef3c7; border-color: #f59e0b; }
        .test-item strong { display: block; margin-bottom: 5px; }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
        pre {
            background: #1f2937;
            color: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #130117;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .btn:hover { background: #2e1731; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 PWA Diagnostic Test</h1>
        <p>This page tests if your PWA files are accessible and properly configured.</p>

        <h2>📋 Configuration</h2>
        <div class="test-item">
            <strong>BASE_PATH:</strong>
            <code><?= BASE_PATH === '' ? '(empty - root directory)' : BASE_PATH ?></code>
        </div>
        <div class="test-item">
            <strong>APP_URL:</strong>
            <code><?= APP_URL ?></code>
        </div>
        <div class="test-item">
            <strong>Server Software:</strong>
            <code><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></code>
        </div>

        <h2>🔧 File Accessibility Tests</h2>
        
        <?php
        $basePath = rtrim(BASE_PATH, '/');
        $swUrl = APP_URL . '/sw.js';
        $manifestUrl = APP_URL . '/manifest.json';
        
        // Test sw.js
        $swHeaders = @get_headers($swUrl);
        $swAccessible = $swHeaders && strpos($swHeaders[0], '200') !== false;
        $swContentType = '';
        if ($swHeaders) {
            foreach ($swHeaders as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $swContentType = trim(substr($header, 13));
                    break;
                }
            }
        }
        ?>
        
        <div class="test-item <?= $swAccessible ? 'success' : 'error' ?>">
            <strong>Service Worker (sw.js):</strong>
            <?php if ($swAccessible): ?>
                ✅ Accessible at <code><?= $swUrl ?></code><br>
                Content-Type: <code><?= $swContentType ?></code>
                <?php if (strpos($swContentType, 'javascript') === false): ?>
                    <br>⚠️ <strong>WARNING:</strong> Content-Type should be <code>application/javascript</code> or <code>text/javascript</code>
                <?php endif; ?>
            <?php else: ?>
                ❌ Not accessible at <code><?= $swUrl ?></code><br>
                This will prevent PWA installation!
            <?php endif; ?>
        </div>

        <?php
        // Test manifest.json
        $manifestHeaders = @get_headers($manifestUrl);
        $manifestAccessible = $manifestHeaders && strpos($manifestHeaders[0], '200') !== false;
        $manifestContentType = '';
        if ($manifestHeaders) {
            foreach ($manifestHeaders as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $manifestContentType = trim(substr($header, 13));
                    break;
                }
            }
        }
        ?>
        
        <div class="test-item <?= $manifestAccessible ? 'success' : 'error' ?>">
            <strong>Manifest (manifest.json):</strong>
            <?php if ($manifestAccessible): ?>
                ✅ Accessible at <code><?= $manifestUrl ?></code><br>
                Content-Type: <code><?= $manifestContentType ?></code>
                <?php if (strpos($manifestContentType, 'json') === false): ?>
                    <br>⚠️ <strong>WARNING:</strong> Content-Type should be <code>application/manifest+json</code> or <code>application/json</code>
                <?php endif; ?>
            <?php else: ?>
                ❌ Not accessible at <code><?= $manifestUrl ?></code><br>
                This will prevent PWA installation!
            <?php endif; ?>
        </div>

        <?php
        // Check if .htaccess exists
        $htaccessExists = file_exists(__DIR__ . '/.htaccess');
        ?>
        
        <div class="test-item <?= $htaccessExists ? 'success' : 'warning' ?>">
            <strong>.htaccess File:</strong>
            <?= $htaccessExists ? '✅ Found' : '⚠️ Not found' ?>
        </div>

        <h2>🧪 Browser Tests</h2>
        <div id="browserTests"></div>

        <h2>📝 Recommendations</h2>
        <div class="test-item warning">
            <strong>If files are not accessible:</strong>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Verify <code>.htaccess</code> file is uploaded to the root directory</li>
                <li>Check if <code>mod_mime</code> and <code>mod_headers</code> are enabled on your server</li>
                <li>Ensure <code>sw.js</code> and <code>manifest.json</code> files exist in the root directory</li>
                <li>Check file permissions (should be 644 or 755)</li>
                <li>On InfinityFree, some security restrictions may block certain files</li>
            </ol>
        </div>

        <a href="index.php" class="btn">← Back to Login</a>
    </div>

    <script>
        const testsDiv = document.getElementById('browserTests');
        const basePath = '<?= $basePath ?>';
        
        // Test 1: Service Worker Support
        const swSupported = 'serviceWorker' in navigator;
        testsDiv.innerHTML += `
            <div class="test-item ${swSupported ? 'success' : 'error'}">
                <strong>Service Worker Support:</strong>
                ${swSupported ? '✅ Supported' : '❌ Not supported in this browser'}
            </div>
        `;

        // Test 2: Push Notification Support
        const pushSupported = 'PushManager' in window;
        testsDiv.innerHTML += `
            <div class="test-item ${pushSupported ? 'success' : 'warning'}">
                <strong>Push Notifications:</strong>
                ${pushSupported ? '✅ Supported' : '⚠️ Not supported in this browser'}
            </div>
        `;

        // Test 3: HTTPS
        const isSecure = window.isSecureContext;
        testsDiv.innerHTML += `
            <div class="test-item ${isSecure ? 'success' : 'error'}">
                <strong>Secure Context (HTTPS):</strong>
                ${isSecure ? '✅ Yes' : '❌ No - PWA requires HTTPS'}
            </div>
        `;

        // Test 4: Try to register service worker
        if (swSupported) {
            const swPath = basePath + '/sw.js';
            const swScope = basePath + '/';
            
            console.log('Testing SW registration:', { swPath, swScope });
            
            navigator.serviceWorker.register(swPath, { scope: swScope })
                .then(reg => {
                    testsDiv.innerHTML += `
                        <div class="test-item success">
                            <strong>Service Worker Registration:</strong>
                            ✅ Successfully registered!<br>
                            <small>Scope: <code>${reg.scope}</code></small>
                        </div>
                    `;
                    console.log('✅ SW registered:', reg);
                })
                .catch(err => {
                    testsDiv.innerHTML += `
                        <div class="test-item error">
                            <strong>Service Worker Registration:</strong>
                            ❌ Failed to register<br>
                            <small>Error: ${err.message}</small>
                            <pre>${err.stack || err.toString()}</pre>
                        </div>
                    `;
                    console.error('❌ SW registration failed:', err);
                });
        }

        // Test 5: Fetch manifest
        fetch(basePath + '/manifest.json')
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(manifest => {
                testsDiv.innerHTML += `
                    <div class="test-item success">
                        <strong>Manifest Fetch:</strong>
                        ✅ Successfully fetched and parsed<br>
                        <small>App Name: <code>${manifest.name || 'N/A'}</code></small>
                    </div>
                `;
                console.log('✅ Manifest:', manifest);
            })
            .catch(err => {
                testsDiv.innerHTML += `
                    <div class="test-item error">
                        <strong>Manifest Fetch:</strong>
                        ❌ Failed to fetch<br>
                        <small>Error: ${err.message}</small>
                    </div>
                `;
                console.error('❌ Manifest fetch failed:', err);
            });
    </script>
</body>
</html>
