const fs   = require('fs');
const path = require('path');

const src  = path.join(__dirname, '..', 'node_modules', 'bootstrap-icons');
const dest = path.join(__dirname, '..', 'assets', 'vendors', 'bootstrap-icons');

// The CSS references fonts via relative url("fonts/..."), so keep that structure.
fs.mkdirSync(path.join(dest, 'fonts'), { recursive: true });

// Copy minified CSS
fs.copyFileSync(
    path.join(src, 'font', 'bootstrap-icons.min.css'),
    path.join(dest, 'bootstrap-icons.min.css')
);

// Copy woff/woff2 next to the CSS so relative url("fonts/...") resolves correctly
const fontSrc = path.join(src, 'font', 'fonts');
for (const ext of ['woff', 'woff2']) {
    const file = `bootstrap-icons.${ext}`;
    fs.copyFileSync(path.join(fontSrc, file), path.join(dest, 'fonts', file));
}

console.log('Bootstrap Icons copied to assets/vendors/bootstrap-icons/');
