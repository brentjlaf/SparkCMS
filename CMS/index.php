<?php
// File: index.php
require_once __DIR__ . '/includes/auth.php';
// Load pages from JSON
$pagesFile = __DIR__ . '/data/pages.json';
$pages = [];
if (file_exists($pagesFile)) {
    $json = file_get_contents($pagesFile);
    $pages = json_decode($json, true) ?: [];
}

$settingsFile = __DIR__ . '/data/settings.json';
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
}

$menusFile = __DIR__ . '/data/menus.json';
$menus = [];
if (file_exists($menusFile)) {
    $menus = json_decode(file_get_contents($menusFile), true) ?: [];
}

// Base paths used by theme templates
$scriptBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if (substr($scriptBase, -4) === '/CMS') {
    $scriptBase = substr($scriptBase, 0, -4);
}
$scriptBase = rtrim($scriptBase, '/');

function render_theme_page($templateFile, $page, $scriptBase) {
    global $settings, $menus, $logged_in;
    $themeBase = $scriptBase . '/theme';
    ob_start();
    include $templateFile;
    $html = ob_get_clean();
    $html = preg_replace('/<mwPageArea[^>]*><\/mwPageArea>/', $page['content'], $html);
    if (!$logged_in) {
        $html = preg_replace('#<templateSetting[^>]*>.*?</templateSetting>#si', '', $html);
        $html = preg_replace('#<div class="block-controls"[^>]*>.*?</div>#si', '', $html);
        $html = str_replace('draggable="true"', '', $html);
        $html = preg_replace('#\sdata-ts="[^"]*"#i', '', $html);
    }
    echo $html;
}

// Determine page slug
$slug = isset($_GET['page']) ? $_GET['page'] : ($settings['homepage'] ?? 'home');

$logged_in = is_logged_in();

if ($slug === 'search') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $results = [];
    $lower = strtolower($q);
    foreach ($pages as $p) {
        if (!$logged_in && (empty($p['published']) || ($p['access'] ?? 'public') !== 'public')) {
            continue;
        }
        if ($q === '' || stripos($p['title'], $lower) !== false || stripos($p['slug'], $lower) !== false || stripos($p['content'], $lower) !== false) {
            $results[] = $p;
        }
    }
    $content = '<div class="search-results"><h1>Search Results';
    if ($q !== '') { $content .= ' for &quot;' . htmlspecialchars($q) . '&quot;'; }
    $content .= '</h1>';
    if ($results) {
        $content .= '<ul>';
        foreach ($results as $r) {
            $content .= '<li><a href="' . htmlspecialchars($scriptBase . '/' . $r['slug']) . '">' . htmlspecialchars($r['title']) . '</a></li>';
        }
        $content .= '</ul>';
    } else {
        $content .= '<p>No results found</p>';
    }
    $content .= '</div>';
    $page = ['title' => 'Search', 'content' => $content];
    $templateFile = realpath(__DIR__ . '/../theme/templates/pages/search.php');
    if ($templateFile && file_exists($templateFile)) {
        render_theme_page($templateFile, $page, $scriptBase);
    } else {
        echo $content;
    }
    exit;
}

$pageIndex = null;
$page = null;
foreach ($pages as $i => $p) {
    if ($p['slug'] === $slug) {
        $pageIndex = $i;
        $page = $p;
        break;
    }
}

if (!$page) {
    http_response_code(404);
    $page = ['title' => 'Page Not Found', 'content' => '<h1>Page Not Found</h1>'];
    $templateFile = realpath(__DIR__ . '/../theme/templates/pages/errors/404.php');
    if ($templateFile && file_exists($templateFile)) {
        render_theme_page($templateFile, $page, $scriptBase);
    } else {
        echo $page['content'];
    }
    exit;
}

if (empty($page['published']) && !$logged_in) {
    http_response_code(404);
    $page = ['title' => 'Page Not Found', 'content' => '<h1>Page Not Found</h1>'];
    $templateFile = realpath(__DIR__ . '/../theme/templates/pages/errors/404.php');
    if ($templateFile && file_exists($templateFile)) {
        render_theme_page($templateFile, $page, $scriptBase);
    } else {
        echo $page['content'];
    }
    exit;
}

if (($page['access'] ?? 'public') !== 'public' && !$logged_in) {
    http_response_code(403);
    $page = ['title' => 'Restricted', 'content' => '<h1>Restricted</h1>'];
    $templateFile = realpath(__DIR__ . '/../theme/templates/pages/errors/403.php');
    if ($templateFile && file_exists($templateFile)) {
        render_theme_page($templateFile, $page, $scriptBase);
    } else {
        echo $page['content'];
    }
    exit;
}

// Increment views and persist
if ($pageIndex !== null) {
    $pages[$pageIndex]['views'] = ($pages[$pageIndex]['views'] ?? 0) + 1;
    $page = $pages[$pageIndex];
    file_put_contents($pagesFile, json_encode($pages, JSON_PRETTY_PRINT));
}

// If logged in show the page builder instead of the static page
if ($logged_in) {
    $_GET['id'] = $page['id'];
    require __DIR__ . '/../liveed/builder.php';
    return;
}

$templateFile = null;
if (!empty($page['template'])) {
    $candidate = realpath(__DIR__ . '/../theme/templates/pages/' . $page['template']);
    if ($candidate && file_exists($candidate)) {
        $templateFile = $candidate;
    }
}
if ($templateFile && !$logged_in) {
    render_theme_page($templateFile, $page, $scriptBase);
    return;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
<title><?php echo htmlspecialchars(($settings['site_name'] ?? 'SparkCMS') . ' - ' . $page['title']); ?></title>
<?php if (!empty($page['meta_title'])): ?>
    <meta name="title" content="<?php echo htmlspecialchars($page['meta_title']); ?>">
<?php endif; ?>
<?php if (!empty($page['meta_description'])): ?>
    <meta name="description" content="<?php echo htmlspecialchars($page['meta_description']); ?>">
<?php endif; ?>
<?php if (!empty($page['og_title'])): ?>
    <meta property="og:title" content="<?php echo htmlspecialchars($page['og_title']); ?>">
<?php endif; ?>
<?php if (!empty($page['og_description'])): ?>
    <meta property="og:description" content="<?php echo htmlspecialchars($page['og_description']); ?>">
<?php endif; ?>
<?php if (!empty($page['og_image'])): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($page['og_image']); ?>">
<?php endif; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<?php if ($logged_in): ?>
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/theme/css/root.css">
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/theme/css/essential.css">
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/theme/css/skin.css">
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/theme/css/utilities.css">
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/theme/css/override.css">
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/spark-cms.css">
<?php else: ?>
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/theme/css/root.css">
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/theme/css/essential.css">
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/theme/css/skin.css">
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/theme/css/utilities.css">
<link rel="stylesheet" href="<?php echo $scriptBase; ?>/theme/css/override.css">
<?php endif; ?>
</head>
<body>
<?php if ($logged_in): ?>
<?php else: ?>
    <div class="header">
        <div class="logo">
        <?php if (!empty($settings['logo'])): ?>
            <img src="<?php echo htmlspecialchars($scriptBase . '/CMS/' . $settings['logo']); ?>" alt="Logo" style="height:40px;">
        <?php else: ?>
            <?php echo htmlspecialchars($settings['site_name'] ?? 'SparkCMS'); ?>
        <?php endif; ?>
        </div>
        <div>
            <a class="btn btn-primary" href="<?php echo $scriptBase; ?>/CMS/admin.php">Admin</a>
        </div>
    </div>
    <div class="content">
        <?php echo $page['content']; ?>
    </div>
    <footer class="footer">
        &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name'] ?? 'SparkCMS'); ?>
    </footer>
<?php endif; ?>
<?php if ($logged_in): ?>
<script src="<?php echo $scriptBase; ?>/theme/js/global.js"></script>
<script src="<?php echo $scriptBase; ?>/theme/js/script.js"></script>
<?php else: ?>
<script src="<?php echo $scriptBase; ?>/theme/js/global.js"></script>
<script src="<?php echo $scriptBase; ?>/theme/js/script.js"></script>
<?php endif; ?>
</body>
</html>
