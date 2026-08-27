const fs = require('fs');
const path = require('path');

const DEFAULT_HTTP_PORT = '80';

function readHttpPortFromEnvFile() {
  const envPath = path.resolve(__dirname, '..', '.env');

  try {
    const content = fs.readFileSync(envPath, 'utf8');
    const match = content.match(/^\s*HTTP_PORT\s*=\s*(.*)\s*$/m);

    return match ? match[1].trim() : null;
  } catch {
    return null;
  }
}

const httpPort = process.env.HTTP_PORT || readHttpPortFromEnvFile() || DEFAULT_HTTP_PORT;

module.exports = {
  '/api': {
    target: `http://localhost:${httpPort}`,
    secure: false,
    changeOrigin: true,
  },
};
