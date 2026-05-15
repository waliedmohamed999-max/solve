import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const root = process.cwd();
const publicDir = join(root, 'public');
const distDir = join(root, 'dist');

if (!existsSync(publicDir)) {
  throw new Error('Cannot prepare Vercel output: public directory is missing.');
}

rmSync(distDir, { recursive: true, force: true });
mkdirSync(distDir, { recursive: true });

cpSync(publicDir, distDir, {
  recursive: true,
  filter(source) {
    const normalized = source.replaceAll('\\', '/');

    return !normalized.endsWith('/public/hot')
      && !normalized.includes('/public/storage');
  },
});

console.log('Prepared Vercel static output in dist/.');
