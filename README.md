# Localhost File Explorer

A clean, modern PHP-based file browser for navigating your local web server directories. Perfect for developers who want a visual way to browse and access their localhost projects.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)
![Font Awesome](https://img.shields.io/badge/Font_Awesome-339AF0?style=flat&logo=fontawesome&logoColor=white)


![Localhost File Explorer Demo](Screenshot.png)

## Features

- 📁 **Directory Navigation** - Browse through your localhost directories with an intuitive interface
- 🔗 **Breadcrumb Navigation** - Easily track and navigate your current path
- 📋 **Copy Path** - One-click copy of the full filesystem path to clipboard
- 🔍 **Smart Folder Detection** - Automatically detects folders with index files (index.php, index.html, index.htm)
- ⏰ **Live Date/Time Display** - Real-time clock in the header
- 📂 **Advanced Sorting** - Sort by name, size, or modification date in ascending/descending order
- 👁️ **Dual View Modes** - Toggle between list view (detailed) and grid view (icon-based)
- 🎨 **Modern UI with Themes** - Clean, responsive design with dark/light theme modes
- 🌈 **Customizable Appearance** - Settings modal with hue rotation, font size controls, and background patterns
- 🎭 **Background Patterns** - Choose from 5 geometric patterns with 3D effects (cubes, triangles, squares, columns, rectangles)
- 🔧 **Server Auto-Detection** - Automatically detects your server type (Apache, Nginx, IIS, etc.)
- 🚀 **Self-Contained** - All styles included in the file - no external CSS dependencies
- 💾 **Persistent Preferences** - Remembers your view, sort, theme, and appearance settings across sessions

## How It Works

### Folder Behavior
- **Folders with an index file**: Clicking the folder name opens the project directly. A search icon (🔍) allows you to browse the directory contents instead.
- **Folders without an index file**: Clicking navigates into the directory using the file explorer.

### File Behavior
- Files open directly in a new browser tab when clicked.
- File sizes are displayed in bytes.

### View Modes
- **List View**: Traditional detailed view with file metadata
- **Grid View**: Icon-based view perfect for visual browsing

### Sorting Options
- **Name**: Alphabetical sorting (A-Z or Z-A)
- **Size**: Sort by file size (smallest to largest or vice versa)
- **Modified**: Sort by last modification date (oldest to newest or newest to oldest)

## Installation

1. Copy the `index.php` file to any directory on your web server
2. Access via your browser: `http://localhost/path/to/explorer/`
3. That's it! No configuration needed.

### Supported Server Software
- **MAMP** (macOS/Windows)
- **WAMP** (Windows)
- **XAMPP** (Cross-platform)
- **USBWebserver** (Portable)
- **Laragon** (Windows)
- **Apache** (standalone)
- **Nginx** (standalone)
- **IIS** (Windows)
- **PHP Built-in Server** (`php -S localhost:8000`)

## Requirements

- PHP 5.6 or higher
- Web server with PHP support
- Modern web browser with JavaScript enabled

## Using the Toolbar

The toolbar at the top of the explorer provides quick access to view and sort controls:

### View Toggle
- **List View** (📋): Shows files in a detailed list with metadata
- **Grid View** (🗂️): Shows files as large icons in a grid layout

### Sort Controls
- **Sort By**: Dropdown to select sorting criteria (Name, Size, Modified)
- **Order**: Button to toggle between ascending (↑) and descending (↓) order

Your preferences are automatically saved using localStorage and persist across browser sessions.

## Settings Modal

Click the gear icon (⚙️) in the top-right corner to open the settings modal, which provides extensive customization options:

### Theme Mode
- **Dark Mode**: Default theme with dark backgrounds and light text
- **Light Mode**: Light theme with white backgrounds and dark text

### Hue Rotation
- Adjust the overall color hue of the interface (0° to 360°)
- Creates unique color variations while maintaining readability

### Font Size
- Control the base font size (12px to 24px)
- Affects all text elements including file names, breadcrumbs, and metadata
- Both list view and grid view adapt to font size changes

### Background Patterns
Choose from 5 geometric patterns with 3D effects:
- **Overlapping Cubes**: Interlocking cube pattern with depth
- **Triangles (3D Effect)**: Triangular pattern with 3D perspective
- **Squares (3D Effect)**: Square-based pattern with depth illusion
- **Cube Columns**: Vertical columns of cubes
- **Rectangles (3D Effect)**: Rectangular pattern with 3D appearance

**Pattern Features:**
- Patterns automatically adapt to dark/light theme modes
- Subtle gradient overlay reduces visual intensity for better readability
- Smooth transitions when switching patterns
- "None" option shows a clean gradient background

### Reset to Defaults
- One-click button to restore all settings to their original values
- Clears all localStorage preferences

All settings are automatically saved to localStorage and persist across browser sessions.

## Troubleshooting

### "Failed opening required..." Error

If you see an error like:
```
Failed opening required 'C:/path/to/root/index.php' (include_path='.;C:\php\pear') in Unknown on line 0
```

This is **NOT** caused by this file explorer. This error indicates your PHP configuration has an `auto_prepend_file` directive that's trying to include a file that doesn't exist.

**Solution:**
1. Check your `php.ini` file for `auto_prepend_file` directive
2. Look in your server's configuration (httpd.conf, .htaccess, etc.)
3. For USBWebserver, check the `settings` folder for PHP configuration
4. Either remove the directive or ensure the referenced file exists

### "Access denied" Message

This appears when attempting to navigate outside the explorer's root directory. This is a security feature.

### Blank Page / No Output

1. Check PHP error logs
2. Ensure PHP is properly configured
3. Verify file permissions (readable by web server)

### Styles Not Loading

The explorer is self-contained with all CSS inline. If styles appear broken:
1. Ensure JavaScript is enabled (for some dynamic features)
2. Check browser console for errors
3. Try a hard refresh (Ctrl+Shift+R)

### View/Sort Settings Not Persisting

1. Ensure cookies/localStorage are enabled in your browser
2. Try clearing browser cache and reloading
3. Check browser console for JavaScript errors

## Security

- **Path Sanitization**: The explorer prevents directory traversal attacks by validating that all accessed paths remain within the base directory
- **Access Control**: Attempting to access paths outside the root directory will result in an "Access denied" message
- **No Write Access**: This tool only reads and displays files - it cannot modify, delete, or create files
- **Client-Side Storage**: Preferences are stored locally in your browser using localStorage (no server-side storage)

## Customization

### Adding Index File Types
To recognize additional index files, modify the `$indexFiles` array at the top of the file:

```php
$indexFiles = ['index.php', 'index.html', 'index.htm', 'default.aspx', 'index.jsx'];
```

### Changing Colors
Modify the CSS variables in the `:root` selector:

```css
:root {
    --accent: #3b82f6;    /* Link/button color */
    --bg-dark: #1e293b;   /* Background color */
    --bg-card: #334155;   /* Card background */
}
```

### Adding New Sort Options
To add additional sorting criteria, modify the sorting function in the PHP code:

```php
case 'type':
    $result = strcasecmp(pathinfo($a['name'], PATHINFO_EXTENSION), 
                         pathinfo($b['name'], PATHINFO_EXTENSION));
    break;
```

### Customizing Theme Colors
To modify the theme colors for dark and light modes, update the CSS variables in the JavaScript settings:

```javascript
// Dark mode colors
document.documentElement.style.setProperty('--bg-dark', '#1e293b');
document.documentElement.style.setProperty('--bg-card', '#334155');

// Light mode colors  
document.documentElement.style.setProperty('--bg-dark', '#f1f5f9');
document.documentElement.style.setProperty('--bg-card', '#ffffff');
```

### Adding New Background Patterns
To add a new background pattern, you need to:
1. Add a new CSS class for the pattern
2. Add the pattern to the pattern select dropdown in HTML
3. Update the JavaScript to handle the new pattern

Example pattern CSS:
```css
body.pattern-your-pattern::before {
    --s: 100px;
    --c1: #334155;
    --c2: #475569;
    --c3: #64748b;
    /* Your pattern CSS here */
}
```

### Modifying Pattern Colors
Pattern colors are controlled by CSS custom properties (--c1, --c2, --c3). To change pattern colors for different themes:

```css
/* Dark mode pattern colors */
body.pattern-overlapping-cubes::before {
    --c1: #334155;
    --c2: #475569;
    --c3: #64748b;
}

/* Light mode pattern colors */
body.light-mode.pattern-overlapping-cubes::before {
    --c1: #f2f2f2 !important;
    --c2: #cdcbcc !important;
    --c3: #b9b9b9 !important;
}
```

### Adjusting Gradient Overlay
The gradient overlay that makes patterns less distinct can be modified:

```css
body::after {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.7) 100%);
    /* Adjust opacity or colors as needed */
}

body.light-mode::after {
    background: linear-gradient(135deg, rgba(241, 245, 249, 0.7) 0%, rgba(226, 232, 240, 0.7) 100%);
}
```


## Technical Details

### URL Parameters
The explorer uses URL parameters to maintain state:
- `dir`: Current directory path
- `view`: View mode (`list` or `grid`)
- `sort`: Sort criteria (`name`, `size`, `modified`)
- `order`: Sort order (`asc` or `desc`)

### Data Storage
- **Session Persistence**: Uses `localStorage` to remember user preferences
- **Automatic Redirect**: If no URL parameters are present, loads preferences from localStorage
- **Parameter Preservation**: All navigation links preserve current view/sort settings

### Performance
- **Efficient Sorting**: Uses PHP's `usort()` with optimized comparison functions
- **Minimal Overhead**: Single file with inline CSS and JavaScript
- **Caching Friendly**: No external dependencies except Font Awesome CDN

## License

This project is open source and available for personal and commercial use.

## Contributing

Feel free to fork, modify, and submit pull requests to improve this file explorer!
