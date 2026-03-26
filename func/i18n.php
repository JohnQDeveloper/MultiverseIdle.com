<?php

declare(strict_types=1);

/**
 * Supported language codes mapped to their display names.
 * @return array<string, string>
 */
function supported_languages(): array
{
    return [
        'en' => 'English',
        'es' => 'Español',
        'pt-br' => 'Português (Brasil)',
    ];
}

/**
 * Initialize language from session, cookie, or default to 'en'.
 * Call once during bootstrap (after session_start).
 */
function init_language(): void
{
    $supported = array_keys(supported_languages());

    // 1. Language change via POST
    if (
        isset($_POST['change_language'], $_POST['language'])
        && in_array($_POST['language'], $supported, true)
    ) {
        $lang = $_POST['language'];
        $_SESSION['lang'] = $lang;
        $secure = (bool)ini_get('session.cookie_secure');
        setcookie('mi_lang', $lang, [
            'expires'  => time() + 30 * 86400,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => false,
            'samesite' => 'Strict',
        ]);
        return;
    }

    // 2. Already in session
    if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $supported, true)) {
        return;
    }

    // 3. From cookie
    if (!empty($_COOKIE['mi_lang']) && in_array($_COOKIE['mi_lang'], $supported, true)) {
        $_SESSION['lang'] = $_COOKIE['mi_lang'];
        return;
    }

    // 4. Default
    $_SESSION['lang'] = 'en';
}

/**
 * Get the currently active language code.
 */
function get_language(): string
{
    return $_SESSION['lang'] ?? 'en';
}

/**
 * Translate a key, with optional {placeholder} replacements.
 *
 * @param string                    $key      Translation key
 * @param array<string, int|float|string> $replace  Placeholder => value pairs
 */
function t(string $key, array $replace = []): string
{
    static $translations = null;

    if ($translations === null) {
        $lang    = get_language();
        $en_file = __DIR__ . '/../lang/en.php';

        if ($lang !== 'en') {
            $lang_file = __DIR__ . '/../lang/' . $lang . '.php';
            if (file_exists($lang_file)) {
                /** @var array<string,string> $en_data */
                $en_data = file_exists($en_file) ? (require $en_file) : [];
                /** @var array<string,string> $lang_data */
                $lang_data = require $lang_file;
                $translations = array_merge($en_data, $lang_data);
            } else {
                $translations = file_exists($en_file) ? (require $en_file) : [];
            }
        } else {
            $translations = file_exists($en_file) ? (require $en_file) : [];
        }
    }

    $text = $translations[$key] ?? $key;

    foreach ($replace as $placeholder => $value) {
        $text = str_replace('{' . $placeholder . '}', (string)$value, $text);
    }

    return $text;
}

/**
 * Return a JSON string of all translations (for inline <script> blocks).
 * Use window.MI_LANG[key] in JavaScript.
 */
function t_json(): string
{
    static $translations = null;

    if ($translations === null) {
        $lang    = get_language();
        $en_file = __DIR__ . '/../lang/en.php';

        if ($lang !== 'en') {
            $lang_file = __DIR__ . '/../lang/' . $lang . '.php';
            if (file_exists($lang_file)) {
                $en_data   = file_exists($en_file) ? (require $en_file) : [];
                $lang_data = require $lang_file;
                $translations = array_merge($en_data, $lang_data);
            } else {
                $translations = file_exists($en_file) ? (require $en_file) : [];
            }
        } else {
            $translations = file_exists($en_file) ? (require $en_file) : [];
        }
    }

    return (string)json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
}

/**
 * Localize persisted battle-log HTML at render time.
 * Logs are currently stored in English by background jobs.
 */
function localize_battle_log(string $log_html): string
{
    if ($log_html === '') {
        return $log_html;
    }

    $language = get_language();

    if ($language === 'es') {
        return localize_battle_log_spanish($log_html);
    }

    if ($language === 'pt-br') {
        return localize_battle_log_brazilian_portuguese($log_html);
    }

    return $log_html;
}

function localize_battle_log_spanish(string $log_html): string
{
    if ($log_html === '') {
        return $log_html;
    }

    $out = $log_html;

    $out = preg_replace_callback(
        '/(Party|Monster) (frontline|backline) hits (frontline|backline) for ([0-9]+)(?:\\s+(fire|cold|physical))? damage\\./i',
        static function (array $m): string {
            $attackerSide = strtolower($m[1]) === 'party' ? 'Grupo' : 'Monstruo';
            $attackerPos = strtolower($m[2]) === 'frontline' ? 'frontal' : 'retaguardia';
            $targetPos = strtolower($m[3]) === 'frontline' ? 'frontal' : 'retaguardia';
            $damage = $m[4];
            $type = isset($m[5]) && $m[5] !== '' ? ' (' . strtolower($m[5]) . ')' : '';
            return $attackerSide . ' ' . $attackerPos . ' golpea a ' . $targetPos . ' por ' . $damage . ' de daño' . $type . '.';
        },
        $out
    ) ?? $out;

    $out = preg_replace_callback(
        '/(Party|Monster) (frontline|backline) misses (frontline|backline)\\./i',
        static function (array $m): string {
            $attackerSide = strtolower($m[1]) === 'party' ? 'Grupo' : 'Monstruo';
            $attackerPos = strtolower($m[2]) === 'frontline' ? 'frontal' : 'retaguardia';
            $targetPos = strtolower($m[3]) === 'frontline' ? 'frontal' : 'retaguardia';
            return $attackerSide . ' ' . $attackerPos . ' falla contra ' . $targetPos . '.';
        },
        $out
    ) ?? $out;

    $out = preg_replace('/casts Healing Rain, healing all allies for ([0-9]+)\\./i', 'lanza Lluvia Sanadora, curando a todos los aliados por $1.', $out) ?? $out;
    $out = preg_replace('/casts Greater Heal on ([a-z]+) for ([0-9]+)\\./i', 'lanza Curación Mayor sobre $1 por $2.', $out) ?? $out;
    $out = preg_replace('/casts Firestorm, scorching and hitting ([a-z]+) for ([0-9]+) fire damage\\./i', 'lanza Tormenta de Fuego, abrasando y golpeando a $1 por $2 de daño de fuego.', $out) ?? $out;
    $out = preg_replace('/casts Blizzard, chilling and hitting ([a-z]+) for ([0-9]+) cold damage\\./i', 'lanza Ventisca, enfriando y golpeando a $1 por $2 de daño de hielo.', $out) ?? $out;

    $out = preg_replace('/Monster stats are ([0-9]+) STR,\\s*([0-9]+) DEX,\\s*([0-9]+) HP,\\s*([0-9]+) WIS\\./i', 'Estadísticas del monstruo: $1 FUE, $2 DES, $3 SAL, $4 SAB.', $out) ?? $out;
    $out = preg_replace('/You won the arena battle on floor ([0-9]+)!/i', '¡Ganaste la batalla de arena en el piso $1!', $out) ?? $out;
    $out = preg_replace('/You lost the arena battle on floor ([0-9]+)\\./i', 'Perdiste la batalla de arena en el piso $1.', $out) ?? $out;

    $out = str_replace('<summary>Battle Log</summary>', '<summary>Registro de batalla</summary>', $out);

    return $out;
}

function localize_battle_log_brazilian_portuguese(string $log_html): string
{
    if ($log_html === '') {
        return $log_html;
    }

    $out = $log_html;

    $out = preg_replace_callback(
        '/(Party|Monster) (frontline|backline) hits (frontline|backline) for ([0-9]+)(?:\\s+(fire|cold|physical))? damage\\./i',
        static function (array $m): string {
            $attackerSide = strtolower($m[1]) === 'party' ? 'Grupo' : 'Monstro';
            $attackerPos = strtolower($m[2]) === 'frontline' ? 'linha de frente' : 'retaguarda';
            $targetPos = strtolower($m[3]) === 'frontline' ? 'linha de frente' : 'retaguarda';
            $damage = $m[4];
            $type = isset($m[5]) && $m[5] !== '' ? ' (' . strtolower($m[5]) . ')' : '';

            return $attackerSide . ' ' . $attackerPos . ' atinge a ' . $targetPos . ' causando ' . $damage . ' de dano' . $type . '.';
        },
        $out
    ) ?? $out;

    $out = preg_replace_callback(
        '/(Party|Monster) (frontline|backline) misses (frontline|backline)\\./i',
        static function (array $m): string {
            $attackerSide = strtolower($m[1]) === 'party' ? 'Grupo' : 'Monstro';
            $attackerPos = strtolower($m[2]) === 'frontline' ? 'linha de frente' : 'retaguarda';
            $targetPos = strtolower($m[3]) === 'frontline' ? 'linha de frente' : 'retaguarda';

            return $attackerSide . ' ' . $attackerPos . ' erra a ' . $targetPos . '.';
        },
        $out
    ) ?? $out;

    $out = preg_replace('/casts Healing Rain, healing all allies for ([0-9]+)\\./i', 'lança Chuva de Cura, curando todos os aliados em $1.', $out) ?? $out;
    $out = preg_replace('/casts Greater Heal on ([a-z]+) for ([0-9]+)\\./i', 'lança Cura Maior em $1 por $2.', $out) ?? $out;
    $out = preg_replace('/casts Firestorm, scorching and hitting ([a-z]+) for ([0-9]+) fire damage\\./i', 'lança Tempestade de Fogo, queimando e atingindo $1 com $2 de dano de fogo.', $out) ?? $out;
    $out = preg_replace('/casts Blizzard, chilling and hitting ([a-z]+) for ([0-9]+) cold damage\\./i', 'lança Nevasca, congelando e atingindo $1 com $2 de dano de gelo.', $out) ?? $out;

    $out = preg_replace('/Monster stats are ([0-9]+) STR,\\s*([0-9]+) DEX,\\s*([0-9]+) HP,\\s*([0-9]+) WIS\\./i', 'Os atributos do monstro são $1 FOR, $2 DES, $3 VIDA, $4 SAB.', $out) ?? $out;
    $out = preg_replace('/You won the arena battle on floor ([0-9]+)!/i', 'Você venceu a batalha da arena no andar $1!', $out) ?? $out;
    $out = preg_replace('/You lost the arena battle on floor ([0-9]+)\\./i', 'Você perdeu a batalha da arena no andar $1.', $out) ?? $out;

    $out = str_replace('<summary>Battle Log</summary>', '<summary>Registro de batalha</summary>', $out);

    return $out;
}

/**
 * Localize persisted world boss history log text at render time.
 * Logs are currently stored in English by a cron job.
 */
function localize_world_boss_log(string $log_text): string
{
    if ($log_text === '') {
        return $log_text;
    }

    $language = get_language();

    if ($language === 'es') {
        return localize_world_boss_log_spanish($log_text);
    }

    if ($language === 'pt-br') {
        return localize_world_boss_log_brazilian_portuguese($log_text);
    }

    return $log_text;
}

function localize_world_boss_log_spanish(string $log_text): string
{
    if ($log_text === '') {
        return $log_text;
    }

    $out = $log_text;

    $out = preg_replace_callback(
        '/Rank\\s+#([0-9]+):\\s+Dealt\\s+([0-9,]+)\\s+damage\\s+\\(([0-9.]+)%\\),\\s+awarded\\s+([0-9,]+)\\s+gold(?:\\s+\\(-([0-9,]+)\\s+guild\\s+tax\\))?\\s+and\\s+([0-9,]+)\\s+XP\\./i',
        static function (array $m): string {
            $rank = $m[1];
            $damage = $m[2];
            $pct = $m[3];
            $gold = $m[4];
            $tax = $m[5] ?? '';
            $xp = $m[6];

            $tax_part = $tax !== '' ? ' (-' . $tax . ' de impuesto del gremio)' : '';
            return 'Puesto #' . $rank . ': Infligiste ' . $damage . ' de daño (' . $pct . '%), recibiste ' . $gold . ' de oro' . $tax_part . ' y ' . $xp . ' de XP.';
        },
        $out
    ) ?? $out;

    return $out;
}

function localize_world_boss_log_brazilian_portuguese(string $log_text): string
{
    if ($log_text === '') {
        return $log_text;
    }

    $out = $log_text;

    $out = preg_replace_callback(
        '/Rank\\s+#([0-9]+):\\s+Dealt\\s+([0-9,]+)\\s+damage\\s+\\(([0-9.]+)%\\),\\s+awarded\\s+([0-9,]+)\\s+gold(?:\\s+\\(-([0-9,]+)\\s+guild\\s+tax\\))?\\s+and\\s+([0-9,]+)\\s+XP\\./i',
        static function (array $m): string {
            $rank = $m[1];
            $damage = $m[2];
            $pct = $m[3];
            $gold = $m[4];
            $tax = $m[5] ?? '';
            $xp = $m[6];

            $taxPart = $tax !== '' ? ' (-' . $tax . ' de imposto da guilda)' : '';

            return 'Posição #' . $rank . ': Você causou ' . $damage . ' de dano (' . $pct . '%), recebeu ' . $gold . ' de ouro' . $taxPart . ' e ' . $xp . ' de XP.';
        },
        $out
    ) ?? $out;

    return $out;
}
