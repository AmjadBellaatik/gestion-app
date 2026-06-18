const { pdfToPng } = require('C:/Users/Fatima Mekkas/AppData/Roaming/npm/node_modules/pdf-to-png-converter');
const path = require('path');

const pdfDir = 'C:/laragon/www/gestion-app/storage/app/test-pdfs';
const imgDir = 'C:/laragon/www/gestion-app/storage/app/test-pdfs/screenshots';

const pdfs = [
    'FAC-2026-0001.pdf',
    'DEV-2026-0001.pdf',
    'BL-2026-0001.pdf',
    'BC-2026-0001.pdf',
    'GAR-2026-0001.pdf',
    'PRSK-2026-0001.pdf',
    'FAC-REP-2026-0001.pdf',
    'FAC-2026-MULTI.pdf',
];

(async () => {
    for (const pdfName of pdfs) {
        const base = path.basename(pdfName, '.pdf');
        const pdfPath = `${pdfDir}/${pdfName}`;

        try {
            const pages = await pdfToPng(pdfPath, {
                disableFontFace: false,
                useSystemFonts: true,
                viewportScale: 1.5,
                outputFileMaskFunc: (idx) => `${base}-p${idx + 1}.png`,
                outputFolder: imgDir,
            });

            console.log(`${pdfName}: ${pages.length} page(s)`);
            for (const p of pages) {
                console.log(`  page ${p.pageNumber}: ${p.path} (${p.width}x${p.height})`);
            }
        } catch (e) {
            console.error(`ERROR ${pdfName}: ${e.message}`);
        }
    }
    console.log('\nDone.');
})();
