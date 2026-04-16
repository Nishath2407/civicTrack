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
function langSwitcher(): string {
    $current = currentLang();
    $uri     = strtok($_SERVER['REQUEST_URI'], '?');
    $params  = $_GET;
    unset($params['lang']);

    $html = '<div class="lang-switcher">';
    foreach (SUPPORTED_LANGS as $code => $label) {
        $params['lang'] = $code;
        $url = $uri . '?' . http_build_query($params);
        $active = $current === $code ? ' lang-active' : '';
        $html .= '<a href="' . htmlspecialchars($url) . '" class="lang-btn' . $active . '">' . htmlspecialchars($label) . '</a>';
    }
    $html .= '</div>';
    return $html;
}
