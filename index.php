<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
if (strpos($base, $realBase) !== 0) {
    die('Access denied.');
}

$items = scandir($base);
$items = array_filter($items, function($item) use ($base) {
    return $item !== '.' && $item !== '..';
});

// Sort: folders first, then files
usort($items, function($a, $b) use ($base) {
    $aIsDir = is_dir($base . '/' . $a);
    $bIsDir = is_dir($base . '/' . $b);
    if ($aIsDir === $bIsDir) {
        return strcasecmp($a, $b);
    }
    return $aIsDir ? -1 : 1;
});

// Full filesystem path (absolute)
$fullPath = $base;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Localhost File Explorer</title>
    <!-- Font Awesome 4.7.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/style.css">
    <style>
        /* Additional styles for breadcrumb and path display */
        .current-path {
            font-family: 'Fira Code', monospace;
            font-size: 0.9rem;
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
            font-size: 0.9rem;
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
            font-size: 0.8rem;
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
        .file-icon { font-size: 1.5rem; width: 2rem; text-align: center; }
        .file-name { flex: 1; font-family: monospace; }
        .file-name a { color: inherit; text-decoration: none; }
        .file-name a:hover { color: var(--accent); }
        .extra-icon {
            margin-left: 0.5rem;
            font-size: 0.9rem;
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
            font-size: 0.9rem;
            color: black;
        }
        .settings-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s;
            color: black;
        }
        .settings-btn:hover {
            background: rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <main>
            <div class="header-row">
                <h1><i class="fa fa-folder-open"></i> Localhost File Explorer</h1>
                <div class="header-right">
                    <div class="datetime" id="datetime"></div>
                    <button class="settings-btn" title="Settings"><i class="fa fa-cog"></i></button>
                </div>
            </div>

            <!-- Breadcrumb navigation with current path -->
            <div class="breadcrumb">
                <div class="breadcrumb-nav">
                    <?php
                    $parts = explode('/', trim($dir, '/'));
                    $pathSoFar = '';
                    echo '<a href="?dir=.">root</a>';
                    foreach ($parts as $part) {
                        if (empty($part)) continue;
                        $pathSoFar .= ($pathSoFar ? '/' : '') . $part;
                        echo ' / <a href="?dir=' . urlencode($pathSoFar) . '">' . htmlspecialchars($part) . '</a>';
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
            <ul class="file-list">
                <?php if ($dir !== '.'): ?>
                <?php
                $parentDir = dirname($dir);
                $parentUrl = $parentDir === '.' ? '?dir=.' : '?dir=' . urlencode($parentDir);
                ?>
                <li>
                    <div class="file-icon"><i class="fa fa-folder-open-o"></i></div>
                    <div class="file-name">
                        <a href="<?php echo htmlspecialchars($parentUrl); ?>">..</a>
                    </div>
                    <div class="file-meta">(parent directory)</div>
                </li>
                <?php endif; ?>

                <?php foreach ($items as $item): 
                    $fullPathItem = $base . '/' . $item;
                    $isDir = is_dir($fullPathItem);
                    $icon = $isDir ? '<i class="fa fa-folder"></i>' : '<i class="fa fa-file-o"></i>';
                    
                    if ($isDir) {
                        $folderRelative = ($dir === '.' ? '' : $dir . '/') . $item;
                        $projectUrl = $folderRelative . '/';  // leads to index
                        $explorerUrl = '?dir=' . urlencode($folderRelative);
                        $hasIndexFile = hasIndex($fullPathItem, $indexFiles);
                    } else {
                        $fileUrl = ($dir === '.' ? '' : $dir . '/') . $item;
                    }
                ?>
                <li>
                    <div class="file-icon"><?php echo $icon; ?></div>
                    <div class="file-name">
                        <?php if ($isDir): ?>
                            <?php if ($hasIndexFile): ?>
                                <!-- Folder with index: main link = project, extra icon = explorer -->
                                <a href="<?php echo htmlspecialchars($projectUrl); ?>"><?php echo htmlspecialchars($item); ?></a>
                                <a href="<?php echo htmlspecialchars($explorerUrl); ?>" class="extra-icon" title="Browse directory contents"><i class="fa fa-search"></i></a>
                            <?php else: ?>
                                <!-- Folder without index: single explorer link -->
                                <a href="<?php echo htmlspecialchars($explorerUrl); ?>"><?php echo htmlspecialchars($item); ?></a>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- File: direct link -->
                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank"><?php echo htmlspecialchars($item); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="file-meta">
                        <?php if (!$isDir): ?>
                            <?php echo number_format(filesize($fullPathItem)); ?> bytes
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </main>
    </div>
    <div class="badge"><i class="fa fa-bolt"></i> localhost · MAMP</div>

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
    </script>
</body>
</html>
