// Ringkas laporan error mentah: hitung per file & per identifier
const text = await Bun.file('storage/statis-raw.txt').text();
const lines = text.replace(/^\uFEFF/, '').split(/\r?\n/).filter((l) => l.trim() !== '');

const files: Record<string, number> = {};
const ids: Record<string, number> = {};
const re = /^(.+\.php):(\d+):(.*)$/;
for (const l of lines) {
    const m = l.match(re);
    if (!m) {
        continue;
    }
    const file = m[1].split('\\').slice(-2).join('/');
    files[file] = (files[file] ?? 0) + 1;
    const idMatch = m[3].match(/\((\w+\.\w+)\)\s*$/);
    if (idMatch) ids[idMatch[1]] = (ids[idMatch[1]] ?? 0) + 1;
}
console.log('total:', lines.length, '| files:', Object.keys(files).length);
console.log('=== BY IDENTIFIER ===');
Object.entries(ids).sort((a, b) => b[1] - a[1]).forEach(([k, v]) => console.log(String(v).padStart(4), k));
console.log('=== TOP FILES ===');
Object.entries(files).sort((a, b) => b[1] - a[1]).slice(0, 30).forEach(([k, v]) => console.log(String(v).padStart(4), k));
