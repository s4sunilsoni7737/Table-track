/**
 * Uploads Vite build output (public/build) to AWS S3 when CDN_ENABLED=true.
 * Run after `vite build`. Reads .env from project root.
 */

import { readFileSync, readdirSync, statSync } from 'fs';
import { join, relative } from 'path';
import { fileURLToPath } from 'url';
import { dirname } from 'path';
import { S3Client, PutObjectCommand } from '@aws-sdk/client-s3';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Load .env from project root (parent of scripts/)
function loadEnv() {
    const root = join(__dirname, '..');
    try {
        const envPath = join(root, '.env');
        const content = readFileSync(envPath, 'utf8');
        const env = {};
        content.split('\n').forEach((line) => {
            const m = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/);
            if (m) env[m[1]] = m[2].replace(/^["']|["']$/g, '').trim();
        });
        return env;
    } catch (e) {
        return {};
    }
}

function getAllFiles(dir, base = dir) {
    const files = [];
    const entries = readdirSync(dir, { withFileTypes: true });
    for (const ent of entries) {
        const full = join(dir, ent.name);
        if (ent.isDirectory()) {
            files.push(...getAllFiles(full, base));
        } else {
            files.push(relative(base, full));
        }
    }
    return files;
}

async function uploadDir(s3, bucket, prefix, localDir) {
    const root = join(__dirname, '..', localDir);
    let files;
    try {
        files = getAllFiles(root);
    } catch (e) {
        console.error('Build directory not found:', root);
        throw e;
    }
    const region = process.env.AWS_DEFAULT_REGION || process.env.AWS_REGION || 'ap-south-1';
    const contentType = (path) => {
        if (path.endsWith('.js')) return 'application/javascript';
        if (path.endsWith('.css')) return 'text/css';
        if (path.endsWith('.json')) return 'application/json';
        if (path.endsWith('.woff2')) return 'font/woff2';
        if (path.endsWith('.woff')) return 'font/woff';
        if (path.endsWith('.ttf')) return 'font/ttf';
        if (path.endsWith('.ico')) return 'image/x-icon';
        if (path.endsWith('.svg')) return 'image/svg+xml';
        if (path.endsWith('.png')) return 'image/png';
        if (path.endsWith('.jpg') || path.endsWith('.jpeg')) return 'image/jpeg';
        return 'application/octet-stream';
    };
    for (const file of files) {
        const key = prefix ? `${prefix}/${file}` : file;
        const body = readFileSync(join(root, file));
        await s3.send(
            new PutObjectCommand({
                Bucket: bucket,
                Key: key,
                Body: body,
                ContentType: contentType(file),
                CacheControl: 'public, max-age=31536000, immutable',
                ACL: 'public-read',
            })
        );
        console.log('  uploaded:', key);
    }
}

async function main() {
    const env = loadEnv();
    const cdnEnabled = (env.CDN_ENABLED || '').toLowerCase() === 'true';
    if (!cdnEnabled) {
        console.log('CDN_ENABLED is not true. Skipping S3 upload.');
        process.exit(0);
    }

    const bucket = env.AWS_BUCKET_BUILD;
    const region = (env.AWS_DEFAULT_REGION || env.AWS_REGION || 'ap-south-1').replace(/^['"]|['"]$/g, '');
    if (!bucket) {
        console.error('CDN_ENABLED is true but AWS_BUCKET_BUILD is missing in .env');
        process.exit(1);
    }

    // Optional folder prefix so builds go to e.g. tabletrack/build/ and tabletrack/vendor/
    const folder = (env.AWS_BUCKET_BUILD_FOLDER || '').trim().replace(/^\/+|\/+$/g, '');
    const buildPrefix = folder ? `${folder}/build` : 'build';
    const vendorPrefix = folder ? `${folder}/vendor` : 'vendor';

    // Expose env for AWS SDK
    process.env.AWS_ACCESS_KEY_ID = env.AWS_ACCESS_KEY_ID || process.env.AWS_ACCESS_KEY_ID;
    process.env.AWS_SECRET_ACCESS_KEY = env.AWS_SECRET_ACCESS_KEY || process.env.AWS_SECRET_ACCESS_KEY;
    process.env.AWS_DEFAULT_REGION = region;
    process.env.AWS_REGION = region;

    const buildDir = 'public/build';
    const buildPath = join(dirname(__filename), '..', buildDir);
    try {
        statSync(buildPath);
    } catch (e) {
        console.error('Build directory not found. Run "npm run build" first (Vite will create public/build).');
        process.exit(1);
    }

    const s3 = new S3Client({ region });
    console.log('Uploading build to S3 bucket:', bucket, 'prefix:', buildPrefix + '/');
    await uploadDir(s3, bucket, buildPrefix, buildDir);

    const vendorDir = 'public/vendor';
    const vendorPath = join(dirname(__filename), '..', vendorDir);
    try {
        statSync(vendorPath);
        console.log('Uploading vendor to S3 bucket:', bucket, 'prefix:', vendorPrefix + '/');
        await uploadDir(s3, bucket, vendorPrefix, vendorDir);
    } catch (e) {
        console.warn('Vendor directory not found, skipping:', vendorPath);
    }

    const baseUrl = folder
        ? `https://${bucket}.s3.${region}.amazonaws.com/${folder}`
        : `https://${bucket}.s3.${region}.amazonaws.com`;
    console.log('Done. Set CDN_URL in .env to your base URL, e.g.', baseUrl);
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
