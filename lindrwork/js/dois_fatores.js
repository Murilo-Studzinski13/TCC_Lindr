document.addEventListener('DOMContentLoaded', () => {
  // QR Code gerado no próprio navegador: a chave secreta não sai desta página.
  const qr = document.getElementById('qr');
  if (qr && window.LindrQR) {
    try { qr.innerHTML = LindrQR.svg(qr.dataset.uri, 208); }
    catch (_) { qr.textContent = 'Não foi possível gerar o QR Code. Use a chave abaixo.'; }
  }

  // códigos de backup: copiar / baixar / imprimir
  const list = document.getElementById('backup-list');
  if (!list) return;
  const codes = () => [...list.querySelectorAll('li')].map((li) => li.textContent.trim());

  document.getElementById('copy-codes')?.addEventListener('click', async (e) => {
    try { await navigator.clipboard.writeText(codes().join('\n')); e.target.textContent = 'Copiado!'; }
    catch (_) { e.target.textContent = 'Selecione e copie manualmente'; }
  });
  document.getElementById('download-codes')?.addEventListener('click', () => {
    const blob = new Blob(['Lindr — códigos de backup (cada um vale uma vez)\n\n' + codes().join('\n') + '\n'], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob); a.download = 'lindr-codigos-de-backup.txt'; a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  });
  document.getElementById('print-codes')?.addEventListener('click', () => window.print());
});
