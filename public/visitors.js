'use strict';

// Count only a visible page with JS and the server-issued cookie/proof.
const visit = document.querySelector('meta[name="visit-proof"]');
if (visit) {
  let timer;
  let sent = false;
  const schedule = () => {
    clearTimeout(timer);
    if (sent || document.visibilityState !== 'visible') return;
    timer = setTimeout(async () => {
      if (document.visibilityState !== 'visible') return;
      try {
        const response = await fetch(visit.dataset.endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          body: new URLSearchParams({ proof: visit.content }),
          keepalive: true,
        });
        sent = response.ok;
      } catch { /* A failed visit request must not interrupt the public page. */ }
    }, 1000);
  };
  document.addEventListener('visibilitychange', schedule);
  schedule();
}
