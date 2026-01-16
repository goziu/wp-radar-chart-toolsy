const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

// zipファイルに含めるファイルとディレクトリ
const includeFiles = [
  'wp-radar-chart-toolsy.php',
  'build',
  'README.md'
];

// 除外するファイルとディレクトリ
const excludePatterns = [
  /node_modules/,
  /\.git/,
  /\.DS_Store/,
  /\.zip$/,
  /package\.json/,
  /package-lock\.json/,
  /\.gitignore/,
  /\.npmignore/,
  /build-zip\.js/,
  /copy-frontend-css\.js/,
  /webpack\.config\.js/,
  /src/
];

/**
 * ファイルを再帰的に追加
 */
function addFilesToZip(archive, dir, baseDir = '') {
  const files = fs.readdirSync(dir);

  files.forEach(file => {
    const filePath = path.join(dir, file);
    const relativePath = path.join(baseDir, file);
    const stat = fs.statSync(filePath);

    // 除外パターンのチェック
    const shouldExclude = excludePatterns.some(pattern => {
      if (pattern instanceof RegExp) {
        return pattern.test(filePath) || pattern.test(relativePath);
      }
      return filePath.includes(pattern) || relativePath.includes(pattern);
    });

    if (shouldExclude) {
      return;
    }

    if (stat.isDirectory()) {
      addFilesToZip(archive, filePath, relativePath);
    } else {
      archive.file(filePath, { name: relativePath });
    }
  });
}

/**
 * zipファイルを生成
 */
function createZip() {
  return new Promise((resolve, reject) => {
    const output = fs.createWriteStream('wp-radar-chart-toolsy.zip');
    const archive = archiver('zip', {
      zlib: { level: 9 }
    });

    output.on('close', () => {
      console.log(`✅ zipファイルが生成されました: ${archive.pointer()} bytes`);
      resolve();
    });

    archive.on('error', (err) => {
      reject(err);
    });

    archive.pipe(output);

    // 含めるファイルを追加
    includeFiles.forEach(item => {
      const itemPath = path.join(__dirname, item);
      if (fs.existsSync(itemPath)) {
        const stat = fs.statSync(itemPath);
        if (stat.isDirectory()) {
          addFilesToZip(archive, itemPath, item);
        } else {
          archive.file(itemPath, { name: item });
        }
      }
    });

    archive.finalize();
  });
}

// 実行
createZip().catch(err => {
  console.error('❌ zipファイルの生成に失敗しました:', err);
  process.exit(1);
});
