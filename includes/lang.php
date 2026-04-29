<?php
/**
 * CivicTrack — includes/lang.php
 * Language loader and translation helper.
 * Must be included AFTER session is started (auth.php).
 */

define('SUPPORTED_LANGS', ['en' => 'English', 'hi' => 'हिन्दी', 'te' => 'తెలుగు']);
define('DEFAULT_LANG', 'en');
define('LANG_DIR', dirname(__DIR__) . '/lang/');

/**
 * Load language strings into $GLOBALS['_lang'].
 * Call once after session start.
 */
function loadLang(): void {
    // Set from GET param (lang switcher)
    if (!empty($_GET['lang']) && array_key_exists($_GET['lang'], SUPPORTED_LANGS)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    $code = $_SESSION['lang'] ?? DEFAULT_LANG;
    $file = LANG_DIR . $code . '.php';
    if (!file_exists($file)) $file = LANG_DIR . 'en.php';
    $GLOBALS['_lang'] = require $file;
}

/**
 * Translate a key.
 * @param  string $key     Translation key
 * @param  string $default Fallback text (defaults to key itself)
 * @return string
 */
function t(string $key, string $default = ''): string {
    return $GLOBALS['_lang'][$key] ?? ($default !== '' ? $default : $key);
}

/**
 * Translate and HTML-escape.
 */
function te(string $key, string $default = ''): string {
    return htmlspecialchars(t($key, $default), ENT_QUOTES, 'UTF-8');
}

/**
 * Returns the current language code.
 */
function currentLang(): string {
    return $_SESSION['lang'] ?? DEFAULT_LANG;
}

/**
 * Renders the language switcher HTML.
 */
function langSwitcher() {
    $current = currentLang();
    $langs = [
        'en' => 'English',
        'hi' => 'हिन्दी',
        'te' => 'తెలుగు'
    ];

    $html = '<div class="lang-switcher" style="display: flex; justify-content: center; gap: 10px; margin-top: 15px; flex-wrap: wrap;">';
    
    foreach ($langs as $code => $label) {
        $isActive = ($current == $code);
        
        // Active = Blue, Others = Grey
        $bgColor = $isActive ? '#007bff' : '#6c757d'; 
        
        $html .= '<a href="?lang=' . $code . '" style="' .
                 'background: ' . $bgColor . ';' .
                 'color: white !important;' .
                 'padding: 6px 14px;' .
                 'border-radius: 6px;' .
                 'text-decoration: none;' .
                 'font-size: 13px;' .
                 'font-weight: 500;' .
                 'display: inline-block;' .
                 'border: none;' .
                 'box-shadow: 0 2px 4px rgba(0,0,0,0.1);' .
                 '">' . $label . '</a>';
    }
    
    $html .= '</div>';
    return $html;
}
