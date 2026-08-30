/**
 * Copies the built SPA into Laravel's public/ directory.
 *
 * This exists because Quasar cannot build straight into backend/public: before every build it
 * runs removeBuildArtifacts(), an fse.removeSync() on the whole distDir, which would take
 * index.php and every Filament asset with it. That is Quasar's own step, not Vite's, so
 * build.emptyOutDir does not disable it.
 *
 * So the rule here is the opposite one: never remove a directory wholesale, only the specific
 * entries a previous SPA build wrote. Anything Laravel or Filament owns is left untouched.
 */

import { existsSync } from 'node:fs';
import { cp, readdir, rm } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const frontendDir = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const distDir = join(frontendDir, 'dist', 'spa');
const publicDir = resolve(frontendDir, '..', 'backend', 'public');

/**
 * Directories the SPA owns entirely, safe to clear so hashed filenames from older builds do
 * not pile up. Everything else in public/ belongs to Laravel: index.php, .htaccess,
 * robots.txt, and the css/, js/ and fonts/ trees Filament publishes into.
 */
const spaOwnedDirs = ['assets', 'icons'];

if (!existsSync(distDir)) {
  console.error(`No build found at ${distDir} — run "quasar build" first.`);
  process.exit(1);
}

for (const dir of spaOwnedDirs) {
  await rm(join(publicDir, dir), { recursive: true, force: true });
}

for (const entry of await readdir(distDir)) {
  await cp(join(distDir, entry), join(publicDir, entry), { recursive: true });
}

console.log(`SPA copied into ${publicDir}`);
