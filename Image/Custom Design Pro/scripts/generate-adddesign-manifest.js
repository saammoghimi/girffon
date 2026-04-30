/* eslint-disable no-console */
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const ADD_DESIGN_DIR = path.join(ROOT, 'Images', 'AddDesign');
const OUTPUT_FILE = path.join(ADD_DESIGN_DIR, 'adddesign-manifest.json');
const OUTPUT_SCRIPT = path.join(ADD_DESIGN_DIR, 'adddesign-manifest.js');

if (!fs.existsSync(ADD_DESIGN_DIR)) {
    console.error('AddDesign directory not found:', ADD_DESIGN_DIR);
    process.exit(1);
}

const manifest = {};

function walkDir(dirPath, relativePath) {
    const entries = fs.readdirSync(dirPath, { withFileTypes: true });
    entries.sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base', numeric: true }));

    const files = entries
        .filter(entry => entry.isFile())
        .map(entry => entry.name)
        .filter(name => /\.(png|jpe?g)$/i.test(name));

    if (files.length > 0) {
        const key = relativePath.replace(/\\/g, '/');
        manifest[key] = files;
    }

    entries
        .filter(entry => entry.isDirectory())
        .forEach(entry => {
            const childRelative = relativePath ? `${relativePath}/${entry.name}` : entry.name;
            walkDir(path.join(dirPath, entry.name), childRelative);
        });
}

walkDir(ADD_DESIGN_DIR, '');

fs.writeFileSync(OUTPUT_FILE, JSON.stringify(manifest, null, 2));
console.log('✅ Manifest generated at', OUTPUT_FILE);
const scriptContent = `window.__ADD_DESIGN_MANIFEST = ${JSON.stringify(manifest)};`;
fs.writeFileSync(OUTPUT_SCRIPT, scriptContent);
console.log('✅ Manifest script generated at', OUTPUT_SCRIPT);
console.log('📁 Folders indexed:', Object.keys(manifest).length);
