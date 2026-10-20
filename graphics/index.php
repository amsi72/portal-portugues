<?php
declare(strict_types=1);

// Portal Português 2 — arquivo independente, sem dependência de logos/imagens.
const TURMA = 'Português 2';
const CABECALHO = 'LÍNGUA PORTUGUESA — PORTUGUÊS 2 — 2º SEM. 2026';
const PORTAL_KEY = 'spb-p2-material-v1';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function getMaterials(): array {
    $items = [];
    $base = realpath(__DIR__);
    if ($base === false) return [];
    foreach (new DirectoryIterator($base) as $entry) {
        if ($entry->isDot() || !$entry->isFile() || $entry->isLink() || !$entry->isReadable()) continue;
        $filename = $entry->getFilename();
        if ($filename[0] === '.' || !preg_match('/\.html?$/i', $filename)) continue;
        $real = realpath($entry->getPathname());
        if ($real === false || dirname($real) !== $base) continue;
        $contents = @file_get_contents($real, false, null, 0, 300000);
        $title = '';
        if (is_string($contents) && preg_match('~<title\b[^>]*>(.*?)</title\s*>~is', $contents, $match)) {
            $title = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        if ($title === '') $title = preg_replace('/[_-]+/', ' ', pathinfo($filename, PATHINFO_FILENAME)) ?: $filename;
        $id = hash_hmac('sha256', PORTAL_KEY . ':' . $filename, __DIR__ . ':local-material-key');
        $items[$id] = ['file' => $filename, 'title' => $title];
    }
    uasort($items, static fn(array $a, array $b): int => strnatcasecmp($a['file'], $b['file']));
    return $items;
}

$materials = getMaterials();
$notFound = false;
if (isset($_GET['material'])) {
    $requested = is_string($_GET['material']) ? $_GET['material'] : '';
    if (isset($materials[$requested])) {
        $base = realpath(__DIR__);
        $target = realpath(__DIR__ . DIRECTORY_SEPARATOR . $materials[$requested]['file']);
        if ($base !== false && $target !== false && dirname($target) === $base && is_file($target)) {
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            readfile($target);
            exit;
        }
    }
    http_response_code(404);
    $notFound = true;
}
?><!doctype html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="dark light">
<title>Português 2 — Portal de Materiais | Seminário Presbiteriano de Brasília</title>
<style>
:root{color-scheme:dark;--bg:#0e1625;--panel:#162337;--panel2:#0e1727;--text:#eef3fb;--muted:#aebbd0;--line:#2c3e58;--accent:#63a5ff;--accent-strong:#2f7ee8;--shadow:0 12px 32px #0002}
html[data-theme="light"]{color-scheme:light;--bg:#f3f6fa;--panel:#fff;--panel2:#f7f9fc;--text:#17263b;--muted:#52647a;--line:#d5dfeb;--accent:#1558a6;--accent-strong:#124b91;--shadow:0 12px 32px #17263b0c}
*{box-sizing:border-box}html{min-height:100%;background:var(--bg)}body{margin:0;min-height:100vh;background:var(--bg);color:var(--text);font:16px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;display:flex;flex-direction:column}a{color:var(--accent)}.page{width:min(1120px,calc(100% - 32px));margin-inline:auto}.top{padding:20px 0 0;background:var(--panel);border-bottom:1px solid var(--line)}.head{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:18px;padding-bottom:18px}.institution{display:flex;align-items:center;gap:13px;min-width:0}.spb-mark{width:54px;height:58px;flex:0 0 54px;border-radius:6px;background:linear-gradient(155deg,#15477c 0 70%,#0ca992 70%);display:grid;place-items:center;color:#fff;font-size:26px;font-weight:800}.inst-name{font-size:clamp(18px,2vw,26px);font-weight:760;line-height:1.15}.inst-tag{font-size:12px;color:#20b8a4;font-weight:650;margin-top:5px}.academic{text-align:right;font-size:clamp(11px,1.1vw,14px);font-weight:740;line-height:1.6;max-width:540px;overflow-wrap:anywhere}.prof{font-size:15px;margin-top:6px}.theme-toggle{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border:1px solid var(--line);border-radius:999px;background:var(--panel2);color:var(--text);cursor:pointer;font-size:21px;box-shadow:var(--shadow);grid-column:2;grid-row:1;align-self:start}.theme-toggle:focus-visible,.material:focus-visible,.bunker-link:focus-visible{outline:3px solid var(--accent);outline-offset:3px}.rule{height:3px;background:linear-gradient(90deg,#0ca992,#4b9cff,#c77dff);border:0;margin:0}.main{flex:1;padding:38px 0 48px}.card{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:clamp(18px,3vw,32px);box-shadow:var(--shadow)}h1{font-size:clamp(22px,3vw,30px);line-height:1.2;margin:0 0 10px;color:var(--accent)}.subtitle{color:var(--muted);margin:0 0 22px}.materials{display:grid;gap:10px}.material{display:flex;align-items:center;justify-content:space-between;gap:14px;text-decoration:none;color:var(--text);background:var(--panel2);border:1px solid var(--line);border-left:4px solid var(--accent);border-radius:7px;padding:15px 17px;font-weight:650;overflow-wrap:anywhere;transition:border-color .15s,background .15s}.material:hover{border-color:var(--accent);background:var(--panel)}.arrow{font-size:24px;color:var(--accent);flex:0 0 auto}.empty{color:var(--muted);padding:14px 0}.footer{border-top:1px solid var(--line);padding:22px 0 26px;text-align:center;color:var(--muted);font-size:13px}.footer small{display:block}.bunker-link{display:inline-block;margin-top:7px;color:var(--muted);text-decoration:none;font-weight:700}.bunker-link:hover{color:var(--accent);text-decoration:underline}.error{padding:20px;background:var(--panel);border:1px solid var(--line);border-radius:10px}
@media(max-width:700px){.page{width:calc(100% - 24px)}.top{padding-top:13px}.head{grid-template-columns:minmax(0,1fr) 42px;align-items:start;gap:10px;padding-bottom:13px}.institution{gap:9px}.spb-mark{width:42px;height:46px;flex-basis:42px;font-size:20px}.inst-name{font-size:17px}.inst-tag{font-size:10px;line-height:1.25}.theme-toggle{width:40px;height:40px;font-size:19px}.academic{grid-column:1/-1;grid-row:2;text-align:left;max-width:none;font-size:11px;line-height:1.5;padding-left:51px}.prof{font-size:13px;margin-top:4px}.main{padding:22px 0 30px}.card{padding:18px 14px}.material{padding:13px 11px;font-size:14px}.footer{padding:18px 0 22px}}
@media(max-width:360px){.inst-name{font-size:15px}.inst-tag{max-width:180px}.academic{padding-left:0}.material{font-size:13px}}
@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
</style>
<script>try{const saved=localStorage.getItem('spb-portal-theme');if(saved==='light'||saved==='dark')document.documentElement.dataset.theme=saved}catch(e){}</script>
</head>
<body>
<header class="top"><div class="page"><div class="head">
  <div class="institution"><div class="spb-mark" aria-hidden="true">✝</div><div><div class="inst-name">Seminário Presbiteriano<br>de Brasília</div><div class="inst-tag">Teologia para transformação da vida</div></div></div>
  <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ativar tema claro" title="Alternar tema"><span id="themeIcon" aria-hidden="true">☾</span></button>
  <div class="academic">BACHAREL EM TEOLOGIA<br>DEPARTAMENTO DE CULTURA GERAL<br><?= h(CABECALHO) ?><div class="prof">Prof.: ISMAEL ROCHA</div></div>
</div></div><hr class="rule"></header>
<main class="page main"><section class="card">
<?php if ($notFound): ?><h1>Material não encontrado</h1><p>O material solicitado não está disponível neste portal.</p><p><a href="./">Voltar ao portal de Português 2</a></p>
<?php else: ?><h1>Materiais de Português 2</h1><p class="subtitle">Selecione uma apostila para abrir o material.</p><nav class="materials" aria-label="Materiais disponíveis">
<?php if (!$materials): ?><p class="empty">Nenhuma apostila HTML foi encontrada nesta pasta.</p><?php else: foreach ($materials as $id => $material): ?><a class="material" href="?material=<?= h($id) ?>"><span><?= h($material['title']) ?></span><span class="arrow" aria-hidden="true">›</span></a><?php endforeach; endif; ?>
</nav><?php endif; ?></section></main>
<footer class="footer"><div class="page"><small>© <?= date('Y') ?> Seminário Presbiteriano de Brasília</small><a class="bunker-link" href="https://bunkerdigital.top/" target="_blank" rel="noopener noreferrer">Bunker Digital</a></div></footer>
<script>(function(){const root=document.documentElement,btn=document.getElementById('themeToggle'),icon=document.getElementById('themeIcon');function sync(){const dark=root.dataset.theme!=='light';icon.textContent=dark?'☾':'☀';btn.setAttribute('aria-label',dark?'Ativar tema claro':'Ativar tema escuro')}sync();btn.addEventListener('click',function(){root.dataset.theme=root.dataset.theme==='dark'?'light':'dark';try{localStorage.setItem('spb-portal-theme',root.dataset.theme)}catch(e){}sync()})})();</script>
</body></html>
