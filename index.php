<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent any auto_prepend issues - use this file's directory as the base
$scriptDir = dirname(__FILE__);
chdir($scriptDir);

// List of common index file names
$indexFiles = ['index.php', 'index.html', 'index.htm'];

// Helper: check if a directory contains an index file
function hasIndex($dirPath, $indexFiles) {
    foreach ($indexFiles as $index) {
        if (file_exists($dirPath . '/' . $index)) {
            return true;
        }
    }
    return false;
}

// Get current directory from query string (sanitize)
$dir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$base = realpath($dir);
$realBase = realpath('.');

// View and sort parameters
$view = isset($_GET['view']) ? $_GET['view'] : 'list';   // 'list' or 'grid'
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';    // 'name', 'size', 'modified'
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';  // 'asc' or 'desc'

// Handle realpath failures gracefully
if ($base === false || $realBase === false) {
    die('Error: Unable to resolve directory path. Please check the directory exists.');
}

if (strpos($base, $realBase) !== 0) {
    die('Access denied.');
}

$items = scandir($base);
if ($items === false) {
    die('Error: Unable to read directory contents.');
}

$items = array_filter($items, function($item) use ($base) {
    return $item !== '.' && $item !== '..';
});

// Build an array of file data
$fileData = [];
foreach ($items as $item) {
    $full = $base . '/' . $item;
    $stat = stat($full);
    $fileData[] = [
        'name' => $item,
        'isDir' => is_dir($full),
        'size' => $stat['size'],
        'modified' => $stat['mtime'],
        'fullPath' => $full
    ];
}

// Sorting function
usort($fileData, function($a, $b) use ($sort, $order) {
    // Directories always come first
    if ($a['isDir'] != $b['isDir']) {
        return $a['isDir'] ? -1 : 1;
    }

    $result = 0;
    switch ($sort) {
        case 'name':
            $result = strcasecmp($a['name'], $b['name']);
            break;
        case 'size':
            $result = $a['size'] - $b['size'];
            break;
        case 'modified':
            $result = $a['modified'] - $b['modified'];
            break;
    }

    return ($order == 'asc') ? $result : -$result;
});

// Full filesystem path (absolute)
$fullPath = $base;

// Build extra parameters for preserving view/sort/order
$extraParams = '&view=' . urlencode($view) . '&sort=' . urlencode($sort) . '&order=' . urlencode($order);

// Detect server software for badge
$serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'PHP Server';
if (stripos($serverSoftware, 'Apache') !== false) {
    $serverName = 'Apache';
} elseif (stripos($serverSoftware, 'nginx') !== false) {
    $serverName = 'Nginx';
} elseif (stripos($serverSoftware, 'IIS') !== false) {
    $serverName = 'IIS';
} else {
    $serverName = 'PHP ' . PHP_VERSION;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Localhost File Explorer</title>
    <!-- Font Awesome 4.7.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Russo One font for animated heading -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Russo+One&display=swap">
    <!-- Additional fonts for font family selector -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap">
    <style>
        /* Base styles - self-contained for portability */
        :root {
            --accent: #3b82f6;
            --bg-dark: #1e293b;
            --bg-card: #334155;
            --base-font-size: 16px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #e2e8f0;
            margin: 0;
            padding: 1rem;
            min-height: 100vh;
            position: relative;
            background-color: var(--bg-dark);
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(51, 65, 85, 0.7) 0%, rgba(15, 23, 42, 0.7) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.7) 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        body.light-mode::before {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(0, 0, 0, 0.2) 100%);
        }
        body.light-mode::after {
            background: linear-gradient(135deg, rgba(241, 245, 249, 0.7) 0%, rgba(226, 232, 240, 0.7) 100%);
        }
        body.pattern-none-gradient::before {
            opacity: 1;
        }
        body.pattern-overlapping-cubes::after,
        body.pattern-triangles-3d::after,
        body.pattern-squares-3d::after,
        body.pattern-cube-columns::after,
        body.pattern-rectangles-3d::after {
            opacity: 1;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        main {
            background: var(--bg-card);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            font-size: 1.5rem;
            color: #f1f5f9;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* Additional styles for breadcrumb and path display */
        .current-path {
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: calc(0.9 * var(--base-font-size, 16px));
            background: rgba(0, 0, 0, 0.5);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            word-break: break-all;
            flex: 1;
            color: white;
        }
        .copy-btn {
            background: var(--accent);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: calc(0.9 * var(--base-font-size, 16px));
            cursor: pointer;
            transition: all 0.2s;
            color: white;
            font-weight: 600;
        }
        .copy-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        .copy-feedback {
            font-size: calc(0.8 * var(--base-font-size, 16px));
            margin-left: 0.5rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .file-list { list-style: none; padding: 0; margin: 1rem 0; }
        .file-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: background 0.2s;
        }
        .file-list li:hover { background: rgba(255,255,255,0.05); }
        .file-icon { font-size: calc(1.5 * var(--base-font-size, 16px)); width: 2rem; text-align: center; }
        .file-name { flex: 1; font-family: monospace; font-size: calc(0.9 * var(--base-font-size, 16px)); }
        .file-name a { color: inherit; text-decoration: none; }
        .file-name a:hover { color: var(--accent); }
        .file-meta { color: #94a3b8; font-size: calc(0.85 * var(--base-font-size, 16px)); }
        .extra-icon {
            margin-left: 0.5rem;
            font-size: calc(0.9 * var(--base-font-size, 16px));
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .extra-icon:hover { opacity: 1; }
        .breadcrumb {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 0.75rem;
            background: rgba(0,0,0,0.2);
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .breadcrumb-nav {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .breadcrumb a { color: var(--accent); }
        .path-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .header-row h1 {
            margin: 0;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .datetime {
            font-size: calc(0.9 * var(--base-font-size, 16px));
            color: #94a3b8;
        }
        .settings-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: calc(1.2 * var(--base-font-size, 16px));
            cursor: pointer;
            transition: all 0.2s;
            color: #94a3b8;
        }
        .settings-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        .badge {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            background: var(--bg-card);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: calc(0.8 * var(--base-font-size, 16px));
            color: #94a3b8;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        /* Toolbar styles */
        .toolbar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .view-toggle, .sort-controls {
            display: flex;
            gap: 0.5rem;
        }
        .view-btn, .order-btn, .sort-controls select {
            background: rgba(255,255,255,0.1);
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }
        .view-btn.active, .view-btn:hover, .order-btn:hover, .sort-controls select:hover {
            background: var(--accent);
        }
        .sort-controls select {
            font-family: inherit;
            font-size: calc(0.9 * var(--base-font-size, 16px));
        }

        /* Grid view styles */
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.25rem;
            margin: 1rem 0;
        }
        .file-grid .file-card {
            background: rgba(255,255,255,0.05);
            border-radius: 0.75rem;
            padding: 1.25rem 1rem;
            text-align: center;
            transition: transform 0.2s;
        }
        .file-grid .file-card:hover {
            transform: translateY(-2px);
            background: rgba(255,255,255,0.1);
        }
        .file-grid .file-icon {
            font-size: calc(3 * var(--base-font-size, 16px));
            margin-bottom: 0.5rem;
        }
        .file-grid .file-name {
            word-break: break-word;
            font-size: calc(0.9 * var(--base-font-size, 16px));
        }
        .file-grid .file-meta {
            font-size: calc(0.7 * var(--base-font-size, 16px));
            margin-top: 0.5rem;
        }

        /* Modal overlay */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: var(--bg-card);
            border-radius: 1rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            animation: fadeInUp 0.2s ease;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .modal-header h3 {
            margin: 0;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
        }
        .modal-close:hover {
            color: white;
        }
        .modal-body {
            padding: 1.5rem;
        }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Setting group styles */
        .setting-group {
            margin-bottom: 1.5rem;
        }
        .setting-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #e2e8f0;
        }
        body.light-mode .setting-group label {
            color: #334155;
        }
        .theme-toggle {
            display: flex;
            gap: 0.5rem;
        }
        .theme-btn {
            flex: 1;
            background: rgba(255,255,255,0.1);
            border: none;
            padding: 0.75rem;
            border-radius: 0.5rem;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .theme-btn.active {
            background: var(--accent);
            color: white;
        }
        .theme-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        #hue-slider, #fontsize-slider {
            width: 100%;
            margin-top: 0.5rem;
        }
        #font-select,
        #pattern-select {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border: none;
            padding: 0.75rem;
            border-radius: 0.5rem;
            color: #e2e8f0;
            font-family: inherit;
        }
        #font-select option,
        #pattern-select option {
            background: #334155;
            color: #e2e8f0;
        }
        body.light-mode #font-select,
        body.light-mode #pattern-select {
            background: rgba(0, 0, 0, 0.1);
            color: #334155;
        }
        body.light-mode #font-select option,
        body.light-mode #pattern-select option {
            background: #ffffff;
            color: #334155;
        }
        .btn-primary, .btn-secondary {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #94a3b8;
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Animation for modal */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Background Pattern Styles */
        body.pattern-overlapping-cubes::after {
            --s: 84px; /* control the size*/
            --c1: #334155;
            --c2: #475569;
            --c3: #64748b;
            
            --_g: 0 120deg,#0000 0;
            background:
                conic-gradient(             at calc(250%/3) calc(100%/3),var(--c3) var(--_g)),
                conic-gradient(from -120deg at calc( 50%/3) calc(100%/3),var(--c2) var(--_g)),
                conic-gradient(from  120deg at calc(100%/3) calc(250%/3),var(--c1) var(--_g)),
                conic-gradient(from  120deg at calc(200%/3) calc(250%/3),var(--c1) var(--_g)),
                conic-gradient(from -180deg at calc(100%/3) 50%,var(--c2)  60deg,var(--c1) var(--_g)),
                conic-gradient(from   60deg at calc(200%/3) 50%,var(--c1)  60deg,var(--c3) var(--_g)),
                conic-gradient(from  -60deg at 50% calc(100%/3),var(--c1) 120deg,var(--c2) 0 240deg,var(--c3) 0);
            background-size: calc(var(--s)*sqrt(3)) var(--s);
        }

        body.pattern-triangles-3d::after {
            --s: 105px; /* control the size*/
            --c1: #334155;
            --c2: #475569;
            --c3: #64748b;
            
            background:
                conic-gradient(from 75deg,var(--c1)   15deg ,var(--c2) 0 30deg ,#0000 0 180deg,
                              var(--c2) 0 195deg,var(--c1) 0 210deg,#0000 0) 
                   calc(var(--s)/2) calc(.5*var(--s)/tan(30deg)),
                conic-gradient(var(--c1)   30deg ,var(--c3) 0 75deg ,var(--c1) 0 90deg, var(--c2) 0 105deg,
                   var(--c3) 0 150deg,var(--c2) 0 180deg,var(--c3) 0 210deg,var(--c1) 0 256deg,
                   var(--c2) 0 270deg,var(--c1) 0 286deg,var(--c2) 0 331deg,var(--c3) 0);
            background-size: var(--s) calc(var(--s)/tan(30deg));
        }

        body.pattern-squares-3d::after {
            --s: 222px; /* control the size*/
            --c1: #334155;
            --c2: #475569;
            --c3: #64748b;
            
            --_g: var(--c1) 10%,var(--c2) 10.5% 19%,#0000 19.5% 80.5%,var(--c2) 81% 89.5%,var(--c3) 90%;
            --_c: from -90deg at 37.5% 50%,#0000 75%;
            --_l1: linear-gradient(145deg,var(--_g));
            --_l2: linear-gradient( 35deg,var(--_g));
            background: 
                var(--_l1), var(--_l1) calc(var(--s)/2) var(--s),
                var(--_l2), var(--_l2) calc(var(--s)/2) var(--s),
                conic-gradient(var(--_c),var(--c1) 0) calc(var(--s)/8) 0,
                conic-gradient(var(--_c),var(--c3) 0) calc(var(--s)/2) 0,
                linear-gradient(90deg,var(--c3) 38%,var(--c1) 0 50%,var(--c3) 0 62%,var(--c1) 0);
            background-size: var(--s) calc(2*var(--s)/3);
        }

        body.pattern-cube-columns::after {
            --s: 82px; /* control the size*/
            --c1: #334155;
            --c2: #475569;
            --c3: #64748b;
            
            --_g: var(--c3) 0 120deg,#0000 0;
            background:
                conic-gradient(from -60deg at 50% calc(100%/3),var(--_g)),
                conic-gradient(from 120deg at 50% calc(200%/3),var(--_g)),
                conic-gradient(from  60deg at calc(200%/3),var(--c3) 60deg,var(--c2) 0 120deg,#0000 0),
                conic-gradient(from 180deg at calc(100%/3),var(--c1) 60deg,var(--_g)),
                linear-gradient(90deg,var(--c1)   calc(100%/6),var(--c2) 0 50%,
                          var(--c1) 0 calc(500%/6),var(--c2) 0);
            background-size: calc(1.732*var(--s)) var(--s);
        }

        body.pattern-rectangles-3d::after {
            --s: 194px; /* control the size*/
            --c1: #334155;
            --c2: #475569;
            --c3: #64748b;
            
            --_l:#0000 calc(25%/3),var(--c1) 0 25%,#0000 0;
            --_g:conic-gradient(from 120deg at 50% 87.5%,var(--c1) 120deg,#0000 0);
            background:
                var(--_g),var(--_g) 0 calc(var(--s)/2),
                conic-gradient(from 180deg at 75%,var(--c2) 60deg,#0000 0),
                conic-gradient(from 60deg at 75% 75%,var(--c1) 0 60deg,#0000 0),
                linear-gradient(150deg,var(--_l)) 0 calc(var(--s)/2),
                conic-gradient(at 25% 25%,#0000 50%,var(--c2) 0 240deg,var(--c1) 0 300deg,var(--c2) 0),
                linear-gradient(-150deg,var(--_l)) var(--c3);
            background-size: calc(0.866*var(--s)) var(--s);
        }

        /* Light mode pattern adjustments - more specific to override dark mode */
        body.light-mode.pattern-overlapping-cubes::after {
            --c1: #f2f2f2 !important;
            --c2: #cdcbcc !important;
            --c3: #b9b9b9 !important;
        }

        body.light-mode.pattern-triangles-3d::after {
            --c1: #b9b9b9 !important;
            --c2: #dcdcdc !important;
            --c3: #fafafa !important;
        }

        body.light-mode.pattern-squares-3d::after {
            --c1: #b9b9b9 !important;
            --c2: #dcdcdc !important;
            --c3: #fafafa !important;
        }

        body.light-mode.pattern-cube-columns::after {
            --c1: #b9b9b9 !important;
            --c2: #dcdcdc !important;
            --c3: #fafafa !important;
        }

        body.light-mode.pattern-rectangles-3d::after {
            --c1: #b9b9b9 !important;
            --c2: #dcdcdc !important;
            --c3: #fafafa !important;
        }

        /* SVG Animated Heading Styles */
        .animated-heading-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            height: 60px;
        }
        .animated-heading-wrapper .fa-folder-open {
            font-size: 1.5rem;
            color: #f1f5f9;
            flex-shrink: 0;
        }
        .animated-heading-wrapper svg {
            font-family: "Russo One", sans-serif;
            width: 400px;
            height: 100%;
            flex-shrink: 0;
        }
        .animated-heading-wrapper svg text {
            animation: stroke 5s infinite alternate;
            stroke-width: 2;
            stroke: #3b82f6;
            font-size: 32px;
            dominant-baseline: middle;
        }
        
        /* Light mode adjustments for animated heading */
        body.light-mode .animated-heading-wrapper .fa-folder-open {
            color: #334155;
        }
        
        /* Toolbar and file-card background colors for light mode */
        body.light-mode .toolbar .view-btn,
        body.light-mode .toolbar .order-btn,
        body.light-mode .toolbar .sort-controls select {
            background: rgba(0, 0, 0, 0.1);
            color: #334155;
        }
        body.light-mode .toolbar .view-btn.active,
        body.light-mode .toolbar .view-btn:hover,
        body.light-mode .toolbar .order-btn:hover,
        body.light-mode .toolbar .sort-controls select:hover {
            background: var(--accent);
            color: white;
        }
        body.light-mode .file-grid .file-card {
            background: rgba(0, 0, 0, 0.05);
        }
        body.light-mode .file-grid .file-card:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        body.light-mode .file-list li {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        body.light-mode .file-list li:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        @keyframes stroke {
            0% {
                fill: rgba(59, 130, 246, 0);
                stroke: rgba(59, 130, 246, 1);
                stroke-dashoffset: 25%;
                stroke-dasharray: 0 50%;
                stroke-width: 2;
            }
            70% {
                fill: rgba(59, 130, 246, 0);
                stroke: rgba(59, 130, 246, 1);
            }
            80% {
                fill: rgba(59, 130, 246, 0);
                stroke: rgba(59, 130, 246, 1);
                stroke-width: 3;
            }
            100% {
                fill: rgba(59, 130, 246, 1);
                stroke: rgba(59, 130, 246, 0);
                stroke-dashoffset: -25%;
                stroke-dasharray: 50% 0;
                stroke-width: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <main>
            <div class="header-row">
                <div class="animated-heading-wrapper">
                    <i class="fa fa-folder-open"></i>
                    <svg>
                        <text x="50%" y="50%" dy=".35em" text-anchor="middle">
                             Localhost File Explorer
                        </text>
                    </svg>
                </div>
                <div class="header-right">
                    <div class="datetime" id="datetime"></div>
                    <button class="settings-btn" title="Settings"><i class="fa fa-cog"></i></button>
                </div>
            </div>

            <!-- Toolbar with view and sort controls -->
            <div class="toolbar">
                <div class="view-toggle">
                    <button class="view-btn <?php echo $view == 'list' ? 'active' : ''; ?>" data-view="list" title="List view">
                        <i class="fa fa-list"></i>
                    </button>
                    <button class="view-btn <?php echo $view == 'grid' ? 'active' : ''; ?>" data-view="grid" title="Grid view">
                        <i class="fa fa-th-large"></i>
                    </button>
                </div>
                <div class="sort-controls">
                    <select id="sort-by">
                        <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="size" <?php echo $sort == 'size' ? 'selected' : ''; ?>>Size</option>
                        <option value="modified" <?php echo $sort == 'modified' ? 'selected' : ''; ?>>Modified</option>
                    </select>
                    <button id="sort-order" class="order-btn" title="Toggle order">
                        <i class="fa fa-arrow-<?php echo $order == 'asc' ? 'up' : 'down'; ?>"></i>
                    </button>
                </div>
            </div>

            <!-- Breadcrumb navigation with current path -->
            <div class="breadcrumb">
                <div class="breadcrumb-nav">
                    <?php
                    $parts = explode('/', trim($dir, '/'));
                    $pathSoFar = '';
                    echo '<a href="?dir=.' . $extraParams . '">root</a>';
                    foreach ($parts as $part) {
                        if (empty($part)) continue;
                        $pathSoFar .= ($pathSoFar ? '/' : '') . $part;
                        echo ' / <a href="?dir=' . urlencode($pathSoFar) . $extraParams . '">' . htmlspecialchars($part) . '</a>';
                    }
                    ?>
                </div>
                <div class="path-row">
                    <div class="current-path" id="currentPath"><?php echo htmlspecialchars($fullPath); ?></div>
                    <button class="copy-btn" onclick="copyPath()"><i class="fa fa-clipboard"></i> Copy path</button>
                    <span id="copyFeedback" class="copy-feedback">Copied!</span>
                </div>
            </div>

            <!-- File listing -->
            <?php
            if ($view == 'grid') {
                echo '<div class="file-grid">';
            } else {
                echo '<ul class="file-list">';
            }

            // Parent directory link
            if ($dir !== '.'):
                $parentDir = dirname($dir);
                $parentUrl = $parentDir === '.' ? '?dir=.' . $extraParams : '?dir=' . urlencode($parentDir) . $extraParams;
                
                if ($view == 'grid') {
                    echo '<div class="file-card">';
                    echo '<div class="file-icon"><i class="fa fa-folder-open-o"></i></div>';
                    echo '<div class="file-name">';
                    echo '<a href="' . htmlspecialchars($parentUrl) . '">..</a>';
                    echo '</div>';
                    echo '<div class="file-meta">(parent directory)</div>';
                    echo '</div>';
                } else {
                    echo '<li>';
                    echo '<div class="file-icon"><i class="fa fa-folder-open-o"></i></div>';
                    echo '<div class="file-name">';
                    echo '<a href="' . htmlspecialchars($parentUrl) . '">..</a>';
                    echo '</div>';
                    echo '<div class="file-meta">(parent directory)</div>';
                    echo '</li>';
                }
            endif;

            // File items
            foreach ($fileData as $item) {
                $isDir = $item['isDir'];
                $name = $item['name'];
                $fullPathItem = $item['fullPath'];
                $icon = $isDir ? '<i class="fa fa-folder"></i>' : '<i class="fa fa-file-o"></i>';

                if ($isDir) {
                    $folderRelative = ($dir === '.' ? '' : $dir . '/') . $name;
                    $projectUrl = $folderRelative . '/';
                    $explorerUrl = '?dir=' . urlencode($folderRelative) . $extraParams;
                    $hasIndex = hasIndex($fullPathItem, $indexFiles);
                } else {
                    $fileUrl = ($dir === '.' ? '' : $dir . '/') . $name;
                }

                if ($view == 'grid') {
                    echo '<div class="file-card">';
                    echo '<div class="file-icon">' . $icon . '</div>';
                    echo '<div class="file-name">';
                    if ($isDir) {
                        if ($hasIndex) {
                            echo '<a href="' . htmlspecialchars($projectUrl) . '">' . htmlspecialchars($name) . '</a>';
                            echo '<a href="' . htmlspecialchars($explorerUrl) . '" class="extra-icon" title="Browse"><i class="fa fa-search"></i></a>';
                        } else {
                            echo '<a href="' . htmlspecialchars($explorerUrl) . '">' . htmlspecialchars($name) . '</a>';
                        }
                    } else {
                        echo '<a href="' . htmlspecialchars($fileUrl) . '" target="_blank">' . htmlspecialchars($name) . '</a>';
                    }
                    echo '</div>';
                    if (!$isDir) {
                        echo '<div class="file-meta">' . number_format($item['size']) . ' bytes</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<li>';
                    echo '<div class="file-icon">' . $icon . '</div>';
                    echo '<div class="file-name">';
                    if ($isDir) {
                        if ($hasIndex) {
                            echo '<a href="' . htmlspecialchars($projectUrl) . '">' . htmlspecialchars($name) . '</a>';
                            echo '<a href="' . htmlspecialchars($explorerUrl) . '" class="extra-icon" title="Browse directory contents"><i class="fa fa-search"></i></a>';
                        } else {
                            echo '<a href="' . htmlspecialchars($explorerUrl) . '">' . htmlspecialchars($name) . '</a>';
                        }
                    } else {
                        echo '<a href="' . htmlspecialchars($fileUrl) . '" target="_blank">' . htmlspecialchars($name) . '</a>';
                    }
                    echo '</div>';
                    if (!$isDir) {
                        echo '<div class="file-meta">' . number_format($item['size']) . ' bytes</div>';
                    }
                    echo '</li>';
                }
            }

            if ($view == 'grid') {
                echo '</div>';
            } else {
                echo '</ul>';
            }
            ?>
        </main>
    </div>
    <div class="badge"><i class="fa fa-bolt"></i> localhost · <?php echo htmlspecialchars($serverName); ?></div>

    <!-- Settings Modal -->
    <div id="settings-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa fa-cog"></i> Settings</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Theme mode -->
                <div class="setting-group">
                    <label>Theme Mode</label>
                    <div class="theme-toggle">
                        <button data-theme="light" class="theme-btn"><i class="fa fa-sun-o"></i> Light</button>
                        <button data-theme="dark" class="theme-btn active"><i class="fa fa-moon-o"></i> Dark</button>
                    </div>
                </div>

                <!-- Hue rotation slider -->
                <div class="setting-group">
                    <label>Background Hue Rotation <span id="hue-value">0°</span></label>
                    <input type="range" id="hue-slider" min="0" max="360" step="1" value="0">
                </div>


                <!-- Font size -->
                <div class="setting-group">
                    <label>Font Size <span id="fontsize-value">16px</span></label>
                    <input type="range" id="fontsize-slider" min="12" max="24" step="1" value="16">
                </div>

                <!-- Background Pattern -->
                <div class="setting-group">
                    <label>Background Pattern</label>
                    <select id="pattern-select">
                        <option value="none">None</option>
                        <option value="none-gradient">None (Gradient)</option>
                        <option value="overlapping-cubes">Overlapping Cubes</option>
                        <option value="triangles-3d">Triangles (3D Effect)</option>
                        <option value="squares-3d">Squares (3D Effect)</option>
                        <option value="cube-columns">Cube Columns</option>
                        <option value="rectangles-3d">Rectangles (3D Effect)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button id="reset-settings" class="btn-secondary">Reset to Defaults</button>
                <button id="close-settings" class="btn-primary">Close</button>
            </div>
        </div>
    </div>

    <script>
        function copyPath() {
            const pathElement = document.getElementById('currentPath');
            const text = pathElement.innerText;
            navigator.clipboard.writeText(text).then(() => {
                const feedback = document.getElementById('copyFeedback');
                feedback.style.opacity = '1';
                setTimeout(() => {
                    feedback.style.opacity = '0';
                }, 1500);
            }).catch(err => {
                console.error('Failed to copy: ', err);
                alert('Unable to copy path. You can select it manually.');
            });
        }

        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', options);
        }
        
        // Update immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // View buttons
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const view = this.dataset.view;
                updateURLParameter('view', view);
            });
        });

        // Sort by dropdown
        document.getElementById('sort-by').addEventListener('change', function(e) {
            updateURLParameter('sort', this.value);
        });

        // Order button
        document.getElementById('sort-order').addEventListener('click', function() {
            const currentOrder = '<?php echo $order; ?>';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            updateURLParameter('order', newOrder);
        });

        function updateURLParameter(param, value) {
            // Save to localStorage
            localStorage.setItem('explorer_' + param, value);
            // Update URL and reload
            const url = new URL(window.location.href);
            url.searchParams.set(param, value);
            window.location.href = url.toString();
        }

        // Check localStorage for saved preferences on initial load
        (function() {
            const storedView = localStorage.getItem('explorer_view');
            const storedSort = localStorage.getItem('explorer_sort');
            const storedOrder = localStorage.getItem('explorer_order');
            
            // Only redirect if we have stored preferences AND no URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if ((storedView || storedSort || storedOrder) && 
                !urlParams.has('view') && !urlParams.has('sort') && !urlParams.has('order')) {
                
                const url = new URL(window.location.href);
                if (storedView) url.searchParams.set('view', storedView);
                if (storedSort) url.searchParams.set('sort', storedSort);
                if (storedOrder) url.searchParams.set('order', storedOrder);
                window.location.href = url.toString();
            }
        })();

        // Modal functionality
        const modal = document.getElementById('settings-modal');
        const settingsBtn = document.querySelector('.settings-btn');
        const modalClose = document.querySelector('.modal-close');
        const closeSettingsBtn = document.getElementById('close-settings');
        const resetSettingsBtn = document.getElementById('reset-settings');
        const hueSlider = document.getElementById('hue-slider');
        const hueValue = document.getElementById('hue-value');
        const fontsizeSlider = document.getElementById('fontsize-slider');
        const fontsizeValue = document.getElementById('fontsize-value');
        const patternSelect = document.getElementById('pattern-select');
        const themeButtons = document.querySelectorAll('.theme-btn');

        // Open modal
        settingsBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });

        // Close modal
        function closeModal() {
            modal.style.display = 'none';
        }

        modalClose.addEventListener('click', closeModal);
        closeSettingsBtn.addEventListener('click', closeModal);

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Hue slider
        hueSlider.addEventListener('input', (e) => {
            const value = e.target.value;
            hueValue.textContent = value + '°';
            document.body.style.filter = `hue-rotate(${value}deg)`;
            localStorage.setItem('explorer_hue', value);
        });

        // Font size slider
        fontsizeSlider.addEventListener('input', (e) => {
            const value = e.target.value;
            fontsizeValue.textContent = value + 'px';
            document.documentElement.style.setProperty('--base-font-size', value + 'px');
            localStorage.setItem('explorer_fontsize', value);
        });


        // Pattern select
        patternSelect.addEventListener('change', (e) => {
            const value = e.target.value;
            
            // Remove all pattern classes
            document.body.classList.remove(
                'pattern-none-gradient',
                'pattern-overlapping-cubes',
                'pattern-triangles-3d',
                'pattern-squares-3d',
                'pattern-cube-columns',
                'pattern-rectangles-3d'
            );
            
            // Add selected pattern class
            if (value !== 'none') {
                document.body.classList.add(`pattern-${value}`);
            }
            
            localStorage.setItem('explorer_pattern', value);
        });

        // Theme buttons
        themeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const theme = e.target.dataset.theme;
                themeButtons.forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                
                if (theme === 'light') {
                    document.documentElement.style.setProperty('--bg-dark', '#f1f5f9');
                    document.documentElement.style.setProperty('--bg-card', '#ffffff');
                    document.body.style.color = '#334155';
                    document.body.classList.add('light-mode');
                } else {
                    document.documentElement.style.setProperty('--bg-dark', '#1e293b');
                    document.documentElement.style.setProperty('--bg-card', '#334155');
                    document.body.style.color = '#e2e8f0';
                    document.body.classList.remove('light-mode');
                }
                localStorage.setItem('explorer_theme', theme);
            });
        });

        // Reset settings
        resetSettingsBtn.addEventListener('click', () => {
            // Reset to defaults
            hueSlider.value = 0;
            hueValue.textContent = '0°';
            document.body.style.filter = '';
            
            fontsizeSlider.value = 16;
            fontsizeValue.textContent = '16px';
            document.documentElement.style.setProperty('--base-font-size', '16px');
            
            
            // Reset pattern to none
            patternSelect.value = 'none';
            document.body.classList.remove(
                'pattern-none-gradient',
                'pattern-overlapping-cubes',
                'pattern-triangles-3d',
                'pattern-squares-3d',
                'pattern-cube-columns',
                'pattern-rectangles-3d'
            );
            
            // Reset theme to dark
            themeButtons.forEach(b => b.classList.remove('active'));
            document.querySelector('.theme-btn[data-theme="dark"]').classList.add('active');
            document.documentElement.style.setProperty('--bg-dark', '#1e293b');
            document.documentElement.style.setProperty('--bg-card', '#334155');
            document.body.style.color = '#e2e8f0';
            document.body.classList.remove('light-mode');
            
            // Clear localStorage
            localStorage.removeItem('explorer_hue');
            localStorage.removeItem('explorer_fontsize');
            localStorage.removeItem('explorer_fontfamily');
            localStorage.removeItem('explorer_pattern');
            localStorage.removeItem('explorer_theme');
        });

        // Load saved settings on page load
        (function loadSettings() {
            // Hue rotation
            const savedHue = localStorage.getItem('explorer_hue');
            if (savedHue) {
                hueSlider.value = savedHue;
                hueValue.textContent = savedHue + '°';
                document.body.style.filter = `hue-rotate(${savedHue}deg)`;
            }

            // Font size
            const savedFontSize = localStorage.getItem('explorer_fontsize');
            if (savedFontSize) {
                fontsizeSlider.value = savedFontSize;
                fontsizeValue.textContent = savedFontSize + 'px';
                document.documentElement.style.setProperty('--base-font-size', savedFontSize + 'px');
            }


            // Pattern
            const savedPattern = localStorage.getItem('explorer_pattern');
            if (savedPattern) {
                patternSelect.value = savedPattern;
                
                // Remove all pattern classes
                document.body.classList.remove(
                    'pattern-none-gradient',
                    'pattern-overlapping-cubes',
                    'pattern-triangles-3d',
                    'pattern-squares-3d',
                    'pattern-cube-columns',
                    'pattern-rectangles-3d'
                );
                
                // Add selected pattern class
                if (savedPattern !== 'none') {
                    document.body.classList.add(`pattern-${savedPattern}`);
                }
            }

            // Theme
            const savedTheme = localStorage.getItem('explorer_theme');
            if (savedTheme) {
                themeButtons.forEach(b => b.classList.remove('active'));
                document.querySelector(`.theme-btn[data-theme="${savedTheme}"]`).classList.add('active');
                
                if (savedTheme === 'light') {
                    document.documentElement.style.setProperty('--bg-dark', '#f1f5f9');
                    document.documentElement.style.setProperty('--bg-card', '#ffffff');
                    document.body.style.color = '#334155';
                    document.body.classList.add('light-mode');
                }
            }
        })();
    </script>
</body>
</html>
