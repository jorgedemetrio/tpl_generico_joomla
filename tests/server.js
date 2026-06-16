// Servidor estatico minimo para os testes Playwright.
//
// Serve a RAIZ do repositorio em http://127.0.0.1:<PORT>/, de modo que:
//   - as fixtures sejam acessadas em /tests/fixtures/<arquivo>.html;
//   - os caminhos relativos das fixtures (../../tpl_generico/...) resolvam para
//     os assets REAIS do template servidos a partir da raiz.
// Usar HTTP (em vez de file://) garante que localStorage funcione, o que o
// modal de newsletter precisa para "mostrar apenas no primeiro acesso".
const http = require('http');
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const PORT = process.env.PORT ? Number(process.env.PORT) : 3210;

const TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.gif': 'image/gif',
};

const server = http.createServer((req, res) => {
  try {
    const urlPath = decodeURIComponent((req.url || '/').split('?')[0]);
    const full = path.normalize(path.join(ROOT, urlPath));
    // Impede path traversal para fora da raiz servida.
    if (full !== ROOT && !full.startsWith(ROOT + path.sep)) {
      res.writeHead(403);
      res.end('Forbidden');
      return;
    }
    fs.readFile(full, (err, data) => {
      if (err) {
        res.writeHead(404);
        res.end('Not found');
        return;
      }
      res.writeHead(200, {
        'Content-Type': TYPES[path.extname(full).toLowerCase()] || 'application/octet-stream',
      });
      res.end(data);
    });
  } catch (e) {
    res.writeHead(500);
    res.end('Server error');
  }
});

server.listen(PORT, '127.0.0.1', () => {
  // eslint-disable-next-line no-console
  console.log('Static test server on http://127.0.0.1:' + PORT + '/');
});
