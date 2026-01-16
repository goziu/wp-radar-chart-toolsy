const fs = require('fs');
const path = require('path');

const srcFile = path.join(__dirname, 'src', 'frontend.css');
const destFile = path.join(__dirname, 'build', 'frontend.css');

// buildディレクトリが存在するか確認
const buildDir = path.join(__dirname, 'build');
if (!fs.existsSync(buildDir)) {
    console.error('❌ buildディレクトリが存在しません。先にnpm run buildを実行してください。');
    process.exit(1);
}

// ファイルをコピー
try {
    fs.copyFileSync(srcFile, destFile);
    console.log('✅ frontend.cssをコピーしました');
} catch (error) {
    console.error('❌ frontend.cssのコピーに失敗しました:', error);
    process.exit(1);
}
