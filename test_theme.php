<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Mode Sombre - DriveUs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            margin: 0;
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .test { 
            margin: 10px 0;
            padding: 10px;
            background: #f0f0f0;
            border-left: 4px solid #1e73d9;
            border-radius: 4px;
        }
        .success { border-left-color: #4caf50; color: #2d7a3a; }
        .error { border-left-color: #f44336; color: #c62828; }
        button {
            background: #1e73d9;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px 0;
            font-size: 14px;
        }
        button:hover { background: #1557b0; }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        #console {
            background: #1e1e1e;
            color: #0f0;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            max-height: 300px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Mode Sombre DriveUs</h1>
        
        <div class="test">
            <strong>localStorage Support:</strong>
            <span id="localStorageTest">En test...</span>
        </div>

        <div class="test">
            <strong>Valeur sauvegardee:</strong>
            <span id="themeSaved">En test...</span>
        </div>

        <div class="test">
            <strong>Mode actuel:</strong>
            <span id="currentTheme">En test...</span>
        </div>

        <h2>Actions:</h2>
        <button onclick="testLocalStorage()">1. Test localStorage</button>
        <button onclick="setDarkMode()">2. Activer Mode Sombre</button>
        <button onclick="setLightMode()">3. Activer Mode Clair</button>
        <button onclick="location.reload()">4. Recharger cette page</button>
        <button onclick="goToHomepage()">5. Aller a l'accueil</button>

        <h2>Console:</h2>
        <div id="console"></div>
        <button onclick="clearConsole()">Effacer Console</button>
    </div>

    <script>
        const THEME_KEY = 'driveus_theme';
        const consoleEl = document.getElementById('console');
        const lines = [];

        function log(msg) {
            lines.push(msg);
            if(lines.length > 30) lines.shift();
            consoleEl.textContent = lines.join('\n');
            console.log(msg);
        }

        function clearConsole() {
            lines.length = 0;
            consoleEl.textContent = '';
        }

        function testLocalStorage() {
            try {
                localStorage.setItem('test_key', 'test_value');
                const val = localStorage.getItem('test_key');
                localStorage.removeItem('test_key');
                
                if(val === 'test_value') {
                    document.getElementById('localStorageTest').innerHTML = 
                        '<span class="success">OK - localStorage fonctionne</span>';
                    log('OK: localStorage works');
                    return true;
                }
            } catch(e) {
                document.getElementById('localStorageTest').innerHTML = 
                    '<span class="error">ERROR: ' + e.message + '</span>';
                log('ERROR: ' + e.message);
                return false;
            }
        }

        function updateStatus() {
            const saved = localStorage.getItem(THEME_KEY);
            const isDark = document.documentElement.classList.contains('dark');
            
            document.getElementById('themeSaved').innerHTML = saved ? 
                ('<span class="success">OK - Valeur: ' + saved + '</span>') : 
                '<span class="error">Non defini</span>';

            document.getElementById('currentTheme').innerHTML = isDark ? 
                '<span class="success">Mode SOMBRE actif</span>' : 
                '<span>Mode CLAIR actif</span>';

            log('Status: ' + (isDark ? 'DARK' : 'LIGHT') + ' | Storage: ' + (saved || 'NONE'));
        }

        function setDarkMode() {
            document.documentElement.classList.add('dark');
            document.body.classList.add('dark');
            localStorage.setItem(THEME_KEY, 'dark');
            log('Mode sombre ACTIVE');
            updateStatus();
        }

        function setLightMode() {
            document.documentElement.classList.remove('dark');
            document.body.classList.remove('dark');
            localStorage.setItem(THEME_KEY, 'light');
            log('Mode clair ACTIVE');
            updateStatus();
        }

        function goToHomepage() {
            log('Navigation vers accueil...');
            setTimeout(() => {
                window.location.href = '/DriveUs/Page_d_acceuil.php';
            }, 500);
        }

        // Initial tests
        log('=== Page Test Initialisee ===');
        testLocalStorage();
        updateStatus();
    </script>
</body>
</html>
