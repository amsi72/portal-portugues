<?php
declare(strict_types=1);

/**
 * Portal Acadêmico — Língua Portuguesa — Português 4
 * Seminário Presbiteriano de Brasília
 * Destino de publicação: public_html/portugues/p4/index.php
 */

const TURMA_CODIGO    = 'P4';
const TURMA_TITULO    = 'Português 4';
const TURMA_CABECALHO = 'LÍNGUA PORTUGUESA — PORTUGUÊS 4 — 2º SEM. 2026';
const CHAVE_HMAC      = 'SPB_2026_2_PORTUGUES_4_HMAC_7c3e9a1f5b2d8e4c6a0f2b9d1e7c5a3f';

/**
 * Retorna a lista de materiais HTML válidos presentes exclusivamente no diretório atual (__DIR__).
 *
 * @return array<string, array{arquivo: string, caminho: string, titulo: string, id: string}>
 */
function listarMateriaisDaPasta(string $diretorio): array
{
    $arquivosEncontrados = [];

    try {
        $iterador = new DirectoryIterator($diretorio);
    } catch (Throwable $e) {
        return [];
    }

    foreach ($iterador as $item) {
        if ($item->isDot() || !$item->isFile() || $item->isLink() || !$item->isReadable()) {
            continue;
        }

        $nomeArquivo = $item->getFilename();

        // Ignorar arquivos ocultos, temporários, backups ou de configuração
        if (
            $nomeArquivo[0] === '.' ||
            $nomeArquivo[0] === '_' ||
            substr($nomeArquivo, -1) === '~' ||
            preg_match('/\.(bak|tmp|swp|old|orig|ini|conf|cfg|log|php|phps|phtml|css|js|json|xml|svg|png|jpe?g|gif|webp|ico|pdf|txt|md)$/i', $nomeArquivo)
        ) {
            continue;
        }

        // Aceitar exclusivamente extensão .html (case-insensitive)
        $extensao = strtolower((string) pathinfo($nomeArquivo, PATHINFO_EXTENSION));
        if ($extensao !== 'html') {
            continue;
        }

        $caminhoReal = $item->getRealPath();
        $diretorioReal = realpath($diretorio);

        if ($caminhoReal === false || $diretorioReal === false) {
            continue;
        }

        // Garantir que o arquivo pertence estritamente ao diretório atual
        if (dirname($caminhoReal) !== $diretorioReal) {
            continue;
        }

        $arquivosEncontrados[] = $nomeArquivo;
    }

    // Ordenação natural previsível respeitando números nos nomes (ex.: Aula 1, Aula 2, Aula 10)
    usort($arquivosEncontrados, static function (string $a, string $b): int {
        return strnatcasecmp($a, $b);
    });

    $materiais = [];
    foreach ($arquivosEncontrados as $nomeArquivo) {
        $caminhoCompleto = $diretorio . DIRECTORY_SEPARATOR . $nomeArquivo;
        $identificador = substr(hash_hmac('sha256', TURMA_CODIGO . ':' . $nomeArquivo, CHAVE_HMAC), 0, 32);
        $titulo = extrairTituloDoMaterial($caminhoCompleto, $nomeArquivo);

        $materiais[$identificador] = [
            'arquivo' => $nomeArquivo,
            'caminho' => $caminhoCompleto,
            'titulo'  => $titulo,
            'id'      => $identificador,
        ];
    }

    return $materiais;
}

/**
 * Extrai o conteúdo da tag <title> do arquivo HTML ou formata o nome do arquivo como fallback.
 */
function extrairTituloDoMaterial(string $caminhoArquivo, string $nomeArquivo): string
{
    $conteudo = @file_get_contents($caminhoArquivo, false, null, 0, 16384);

    if (is_string($conteudo) && $conteudo !== '') {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $conteudo, $coincidencias)) {
            $tituloBruto = trim(preg_replace('/\s+/u', ' ', strip_tags($coincidencias[1])) ?? '');

            if ($tituloBruto !== '') {
                if (!mb_check_encoding($tituloBruto, 'UTF-8')) {
                    $tituloBruto = mb_convert_encoding($tituloBruto, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
                }
                $tituloDecodificado = html_entity_decode($tituloBruto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $tituloDecodificado = trim($tituloDecodificado);

                if ($tituloDecodificado !== '') {
                    return $tituloDecodificado;
                }
            }
        }
    }

    // Fallback: remover extensão .html e substituir hífens e sublinhados por espaços
    $semExtensao = (string) preg_replace('/\.html$/i', '', $nomeArquivo);
    $formatado = str_replace(['-', '_'], ' ', $semExtensao);
    $formatado = trim((string) preg_replace('/\s+/u', ' ', $formatado));

    if ($formatado === '') {
        return 'Material Didático';
    }

    return mb_strtoupper(mb_substr($formatado, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($formatado, 1, null, 'UTF-8');
}

/**
 * Escape seguro para exibição em HTML.
 */
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$materiaisDisponiveis = listarMateriaisDaPasta(__DIR__);

// Rota intermediária de entrega por identificador opaco (?material=IDENTIFICADOR)
if (isset($_GET['material'])) {
    $idRequisitado = is_string($_GET['material']) ? trim($_GET['material']) : '';
    $materialSelecionado = null;

    if (preg_match('/^[a-f0-9]{32}$/', $idRequisitado)) {
        foreach ($materiaisDisponiveis as $idValido => $dadosMaterial) {
            if (hash_equals($idValido, $idRequisitado)) {
                $materialSelecionado = $dadosMaterial;
                break;
            }
        }
    }

    if ($materialSelecionado !== null && is_file($materialSelecionado['caminho']) && is_readable($materialSelecionado['caminho'])) {
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        readfile($materialSelecionado['caminho']);
        exit;
    }

    // Resposta apropriada para identificador inválido ou inexistente, sem expor detalhes internos
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material não encontrado — <?= e(TURMA_TITULO) ?></title>
    <style>
        :root {
            --bg: #f7f8fa;
            --surface: #ffffff;
            --text: #1a2433;
            --muted: #5a6779;
            --primary: #15477c;
            --border: #dde3ec;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f1724;
                --surface: #162235;
                --text: #eef2f7;
                --muted: #9fb0c7;
                --primary: #4da3ff;
                --border: #263854;
            }
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .erro-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-top: 4px solid var(--primary);
            border-radius: 8px;
            max-width: 460px;
            width: 100%;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        }
        h1 { font-size: 1.25rem; margin-bottom: 0.75rem; }
        p { color: var(--muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem; }
        a {
            display: inline-block;
            padding: 0.65rem 1.25rem;
            background-color: var(--primary);
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        a:focus-visible { outline: 3px solid var(--primary); outline-offset: 3px; }
    </style>
</head>
<body>
    <main class="erro-card" role="main">
        <h1>Material não encontrado</h1>
        <p>O documento solicitado não está disponível ou o identificador informado é inválido.</p>
        <a href="./">Voltar ao portal de <?= e(TURMA_TITULO) ?></a>
    </main>
</body>
</html>
    <?php
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Português 4 — Portal de Materiais | Seminário Presbiteriano de Brasília</title>
    <script>
        (function () {
            var chave = 'spb_portais_tema';
            var temaSalvo = null;
            try {
                temaSalvo = window.localStorage.getItem(chave);
            } catch (e) {}

            var temaInicial = 'light';
            if (temaSalvo === 'dark' || temaSalvo === 'light') {
                temaInicial = temaSalvo;
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                temaInicial = 'dark';
            }
            document.documentElement.setAttribute('data-theme', temaInicial);
        })();
    </script>
    <style>
        :root,
        html[data-theme="light"] {
            --bg-body: #f5f7fa;
            --bg-surface: #ffffff;
            --bg-surface-hover: #f0f5fb;
            --bg-logo-plate: transparent;
            --border-logo-plate: transparent;
            --spb-logo-text: #15477c;
            --bg-bunker-badge: #12161f;
            --border-bunker-badge: #2c3548;
            --text-primary: #162234;
            --text-secondary: #546274;
            --text-academic-course: #3b4859;
            --brand-blue: #15477c;
            --brand-blue-accent: #15477c;
            --brand-teal: #0ca992;
            --border-color: #dce3ed;
            --header-rule: #15477c;
            --shadow-card: 0 2px 8px rgba(15, 29, 51, 0.05);
            --focus-ring: #15477c;
        }

        html[data-theme="dark"] {
            --bg-body: #0d1522;
            --bg-surface: #152236;
            --bg-surface-hover: #1c2d47;
            --bg-logo-plate: transparent;
            --border-logo-plate: transparent;
            --spb-logo-text: #edf2f8;
            --bg-bunker-badge: #101827;
            --border-bunker-badge: #2b3b55;
            --text-primary: #edf2f8;
            --text-secondary: #a9b8cc;
            --text-academic-course: #cbd6e6;
            --brand-blue: #15477c;
            --brand-blue-accent: #5ca9ff;
            --brand-teal: #17c3aa;
            --border-color: #243754;
            --header-rule: #3b82f6;
            --shadow-card: 0 4px 14px rgba(0, 0, 0, 0.28);
            --focus-ring: #5ca9ff;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-primary);
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            transition: background-color 0.22s ease, color 0.22s ease;
        }

        .container-portal {
            width: 100%;
            max-width: 980px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* 1. CABEÇALHO INSTITUCIONAL */
        .cabecalho-institucional {
            background-color: var(--bg-surface);
            padding-top: 1.25rem;
            transition: background-color 0.22s ease;
        }

        .cabecalho-interno {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.75rem;
            padding-bottom: 1.15rem;
            border-bottom: 3px solid var(--header-rule);
            transition: border-color 0.22s ease;
        }

        .marca-seminario {
            display: inline-flex;
            align-items: center;
            background-color: var(--bg-logo-plate);
            padding: 0.35rem 0.65rem;
            margin-left: -0.35rem;
            border-radius: 8px;
            border: 1px solid var(--border-logo-plate);
            flex-shrink: 0;
            transition: background-color 0.22s ease, border-color 0.22s ease, padding 0.22s ease;
        }

        .logo-spb-svg {
            display: block;
            width: clamp(205px, 25vw, 265px);
            max-width: 100%;
            height: auto;
        }

        .logo-spb-svg .spb-texto-principal {
            fill: var(--spb-logo-text);
            transition: fill 0.22s ease;
        }

        .bloco-direita-cabecalho {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1.15rem;
            margin-left: auto;
            min-width: 0;
        }

        .dados-academicos {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            text-align: right;
            font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-secondary);
            min-width: 0;
            transition: color 0.22s ease;
        }

        .dados-academicos .linha-curso,
        .dados-academicos .linha-departamento {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.055em;
            line-height: 1.35;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .dados-academicos .linha-disciplina {
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1.35;
            text-transform: uppercase;
            color: var(--brand-blue-accent);
            margin-top: 0.28rem;
            padding-top: 0.28rem;
            border-top: 1px solid var(--border-color);
            transition: color 0.22s ease, border-color 0.22s ease;
        }

        .dados-academicos .linha-professor {
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.015em;
            line-height: 1.35;
            color: var(--text-academic-course);
            margin-top: 0.18rem;
        }

        .botao-tema {
            appearance: none;
            background-color: var(--bg-body);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
            flex-shrink: 0;
        }

        .botao-tema:hover {
            background-color: var(--bg-surface-hover);
            border-color: var(--brand-blue-accent);
        }

        .botao-tema:focus-visible {
            outline: 2px solid var(--focus-ring);
            outline-offset: 2px;
        }

        .botao-tema svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        html[data-theme="light"] .icone-sol {
            display: none;
        }

        html[data-theme="dark"] .icone-lua {
            display: none;
        }

        /* 2. LISTA DOS MATERIAIS */
        .conteudo-principal {
            flex: 1;
            padding: 2.5rem 0 3rem;
        }

        .secao-materiais {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: var(--shadow-card);
            transition: background-color 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
        }

        .titulo-secao {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--brand-blue-accent);
            padding-bottom: 0.9rem;
            margin-bottom: 1.35rem;
            border-bottom: 1px solid var(--border-color);
            letter-spacing: -0.01em;
            transition: color 0.22s ease, border-color 0.22s ease;
        }

        .lista-materiais {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .item-material {
            display: block;
        }

        .link-material {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.95rem 1.15rem;
            background-color: var(--bg-body);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--brand-blue-accent);
            border-radius: 7px;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: background-color 0.18s ease, border-color 0.18s ease, transform 0.15s ease;
        }

        .link-material:hover {
            background-color: var(--bg-surface-hover);
            border-color: var(--brand-blue-accent);
        }

        .link-material:focus-visible {
            outline: 2px solid var(--focus-ring);
            outline-offset: 2px;
        }

        .link-material-texto {
            word-break: break-word;
        }

        .link-material-seta {
            color: var(--brand-blue-accent);
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            transition: transform 0.18s ease;
        }

        .link-material:hover .link-material-seta {
            transform: translateX(3px);
        }

        .mensagem-vazia {
            padding: 1.5rem 1rem;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.98rem;
            background-color: var(--bg-body);
            border: 1px dashed var(--border-color);
            border-radius: 7px;
        }

        /* 3. RODAPÉ BUNKER DIGITAL */
        .rodape-institucional {
            padding: 1.25rem 0 2rem;
            text-align: center;
            border-top: 1px solid var(--border-color);
            transition: border-color 0.22s ease;
        }

        .link-bunker {
            display: inline-block;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.5;
            padding: 0.35rem 0.6rem;
            border-radius: 8px;
            transition: color 0.18s ease, background-color 0.18s ease;
        }

        .link-bunker:hover {
            color: var(--brand-blue);
            background-color: var(--bg-surface-hover);
            text-decoration: underline;
        }

        .link-bunker:focus-visible {
            outline: 2px solid var(--focus-ring);
            outline-offset: 4px;
        }

        /* RESPONSIVIDADE TABLETS E TELAS INTERMEDIÁRIAS */
        @media (max-width: 860px) {
            .cabecalho-interno {
                gap: 1.15rem;
            }

            .logo-spb-svg {
                width: 215px;
            }

            .dados-academicos .linha-curso,
            .dados-academicos .linha-departamento {
                font-size: 0.74rem;
            }

            .dados-academicos .linha-disciplina {
                font-size: 0.85rem;
            }

            .dados-academicos .linha-professor {
                font-size: 0.81rem;
            }
        }

        /* RESPONSIVIDADE CELULARES */
        @media (max-width: 640px) {
            .container-portal {
                padding: 0 1rem;
            }

            .cabecalho-institucional {
                padding-top: 1rem;
            }

            .cabecalho-interno {
                position: relative;
                flex-direction: column;
                align-items: stretch;
                gap: 0.85rem;
                padding-bottom: 1rem;
            }

            .marca-seminario {
                align-self: flex-start;
                max-width: calc(100% - 52px);
                margin-left: 0;
                padding: 0.25rem 0.45rem;
            }

            .logo-spb-svg {
                width: 198px;
            }

            .bloco-direita-cabecalho {
                width: 100%;
                margin-left: 0;
                justify-content: flex-start;
            }

            .botao-tema {
                position: absolute;
                top: 0.1rem;
                right: 0;
            }

            .dados-academicos {
                width: 100%;
                align-items: flex-start;
                text-align: left;
                padding-top: 0.65rem;
                border-top: 1px solid var(--border-color);
            }

            .dados-academicos .linha-disciplina {
                border-top: none;
                padding-top: 0;
                margin-top: 0.22rem;
                font-size: 0.86rem;
            }

            .secao-materiais {
                padding: 1.35rem 1.1rem;
            }

            .titulo-secao {
                font-size: 1.22rem;
            }

            .rodape-institucional {
                padding: 1.15rem 0 1.65rem;
            }

        }
    </style>
</head>
<body>
    <!-- 1. Cabeçalho Institucional -->
    <header class="cabecalho-institucional" role="banner">
        <div class="container-portal">
            <div class="cabecalho-interno">
                <div class="marca-seminario" aria-label="Seminário Presbiteriano de Brasília — Teologia para transformação da vida">
                    <!-- Logotipo Institucional em SVG Vetorial Fiel -->
                    <svg class="logo-spb-svg" xmlns="http://www.w3.org/2000/svg" viewBox="8 8 618 264" role="img" aria-label="Seminário Presbiteriano de Brasília — Teologia para transformação da vida">
                        <defs>
                            <clipPath id="spb-icon-clip">
                                <rect x="10" y="12" width="166" height="256" rx="1"/>
                            </clipPath>
                            <mask id="spb-cutout-mask">
                                <rect x="0" y="0" width="200" height="280" fill="#ffffff"/>
                                <!-- Eixo vertical superior e inferior da cruz -->
                                <rect x="88" y="8" width="10" height="32" fill="#000000"/>
                                <rect x="88" y="191" width="10" height="44" fill="#000000"/>
                                <!-- Braços horizontais esquerdo e direito -->
                                <rect x="6" y="96" width="38" height="10" fill="#000000"/>
                                <rect x="142" y="96" width="38" height="10" fill="#000000"/>
                                <!-- Corpo do Ichthys (Peixe) -->
                                <path d="M 93 34 C 33 78, 33 158, 93 194 C 106 202, 121 207, 137 209" stroke="#000000" stroke-width="10.5" fill="none" stroke-linecap="butt"/>
                                <path d="M 93 34 C 153 78, 153 158, 93 194 C 80 202, 65 207, 49 209" stroke="#000000" stroke-width="10.5" fill="none" stroke-linecap="butt"/>
                                <!-- Olho circular do peixe -->
                                <circle cx="93" cy="76" r="5.5" fill="#000000"/>
                            </mask>
                        </defs>

                        <!-- Símbolo Esquerdo (Retângulo Azul + Ichthys + Ondas Verdes) -->
                        <g clip-path="url(#spb-icon-clip)">
                            <rect x="10" y="12" width="166" height="256" fill="#ffffff"/>
                            <path d="M 10 12 H 176 V 225 Q 134.5 198 93 225 Q 51.5 198 10 225 Z" fill="#15477C" mask="url(#spb-cutout-mask)"/>
                            <!-- Três pares de ondas em verde-água institucional -->
                            <path d="M 10 229 Q 51.5 202 93 229 Q 134.5 202 176 229" stroke="#0CA992" stroke-width="9.5" fill="none" stroke-linejoin="miter"/>
                            <path d="M 10 246 Q 51.5 219 93 246 Q 134.5 219 176 246" stroke="#0CA992" stroke-width="9.5" fill="none" stroke-linejoin="miter"/>
                            <path d="M 10 263 Q 51.5 236 93 263 Q 134.5 236 176 263" stroke="#0CA992" stroke-width="9.5" fill="none" stroke-linejoin="miter"/>
                        </g>

                        <!-- Tipografia Institucional Direita -->
                        <g font-family="'Montserrat', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif">
                            <text class="spb-texto-principal" x="204" y="88" fill="#15477C" font-size="55" font-weight="500" letter-spacing="0.3">Seminário</text>
                            <text class="spb-texto-principal" x="204" y="152" fill="#15477C" font-size="55" font-weight="500" letter-spacing="0.3">Presbiteriano</text>
                            <text class="spb-texto-principal" x="204" y="216" fill="#15477C" font-size="55" font-weight="500" letter-spacing="0.3">de Brasília</text>
                            <text x="204" y="253" fill="#0CA992" font-size="21.2" font-weight="600" letter-spacing="0.15">Teologia para transformação da vida</text>
                        </g>
                    </svg>
                </div>

                <div class="bloco-direita-cabecalho">
                    <div class="dados-academicos">
                        <div class="linha-curso">BACHARELADO EM TEOLOGIA</div>
                        <div class="linha-departamento">DEPARTAMENTO DE CULTURA GERAL</div>
                        <div class="linha-disciplina"><?= e(TURMA_CABECALHO) ?></div>
                        <div class="linha-professor">Prof. Ismael Rocha</div>
                    </div>

                    <button type="button" id="botao-alternar-tema" class="botao-tema" aria-label="Alternar para modo escuro" title="Alternar entre tema claro e escuro">
                        <!-- Ícone de Lua (exibido no modo claro para ativar o modo escuro) -->
                        <svg class="icone-lua" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                        <!-- Ícone de Sol (exibido no modo escuro para ativar o modo claro) -->
                        <svg class="icone-sol" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/>
                            <line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- 2. Lista dos Materiais -->
    <main class="conteudo-principal" role="main">
        <div class="container-portal">
            <section class="secao-materiais" aria-labelledby="titulo-materiais">
                <h1 id="titulo-materiais" class="titulo-secao">Materiais de <?= e(TURMA_TITULO) ?></h1>

                <?php if (empty($materiaisDisponiveis)): ?>
                    <p class="mensagem-vazia">Nenhum material disponibilizado no momento.</p>
                <?php else: ?>
                    <ul class="lista-materiais">
                        <?php foreach ($materiaisDisponiveis as $material): ?>
                            <li class="item-material">
                                <a class="link-material" href="?material=<?= e($material['id']) ?>">
                                    <span class="link-material-texto"><?= e($material['titulo']) ?></span>
                                    <span class="link-material-seta" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- 3. Rodapé institucional: link textual, sem logotipo Bunker -->
    <footer class="rodape-institucional" role="contentinfo">
        <div class="container-portal">
            <a class="link-bunker" href="https://bunkerdigital.top/" target="_blank" rel="noopener noreferrer" aria-label="Bunker Digital — abrir site em nova aba">Bunker Digital</a>
        </div>
    </footer>

    <script>
        (function () {
            var chave = 'spb_portais_tema';
            var botao = document.getElementById('botao-alternar-tema');
            var raiz = document.documentElement;

            function atualizarAcessibilidadeBotao(temaAtual) {
                if (!botao) return;
                if (temaAtual === 'dark') {
                    botao.setAttribute('aria-label', 'Alternar para modo claro');
                    botao.setAttribute('title', 'Alternar para modo claro');
                } else {
                    botao.setAttribute('aria-label', 'Alternar para modo escuro');
                    botao.setAttribute('title', 'Alternar para modo escuro');
                }
            }

            atualizarAcessibilidadeBotao(raiz.getAttribute('data-theme') || 'light');

            if (botao) {
                botao.addEventListener('click', function () {
                    var atual = raiz.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
                    var novoTema = atual === 'dark' ? 'light' : 'dark';
                    raiz.setAttribute('data-theme', novoTema);
                    atualizarAcessibilidadeBotao(novoTema);
                    try {
                        window.localStorage.setItem(chave, novoTema);
                    } catch (e) {}
                });
            }
        })();
    </script>
</body>
</html>