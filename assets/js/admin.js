'use strict';

(function () {
  const nonce     = WJC.nonce;
  const restUrl   = WJC.restUrl;
  const connected = WJC.connected;

  // ── Helpers ────────────────────────────────────────────────

  function api(path, opts = {}) {
    return fetch(restUrl + path, {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
        ...(opts.headers || {}),
      },
      ...opts,
    }).then(async (res) => {
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) {
        throw new Error(data.error || 'Request failed.');
      }
      return data;
    });
  }

  function showError(msg) {
    const el = document.getElementById('wjc-error');
    if (!el) return;
    el.textContent = msg;
    el.style.display = msg ? 'block' : 'none';
    document.getElementById('wjc-success').style.display = 'none';
  }

  function showSuccess(msg) {
    const el = document.getElementById('wjc-success');
    if (!el) return;
    el.textContent = msg;
    el.style.display = msg ? 'block' : 'none';
    document.getElementById('wjc-error').style.display = 'none';
  }

  function setLoading(btn, loading, label) {
    if (!btn) return;
    btn.disabled = loading;
    btn.textContent = loading ? 'Please wait…' : label;
  }

  // ── Show correct screen ────────────────────────────────────

  function showScreen(which) {
    document.getElementById('wjc-connected').style.display = which === 'connected' ? 'block' : 'none';
    document.getElementById('wjc-auth').style.display      = which === 'auth'      ? 'block' : 'none';
  }

  function populateConnected(email) {
    const el = document.getElementById('wjc-email');
    if (el) el.textContent = email || WJC.email || '';
  }

  // ── Connect flow ───────────────────────────────────────────

  async function connectWithCredentials(email, licenseKey) {
    const data = await api('/connect', {
      method: 'POST',
      body: JSON.stringify({ email, license_key: licenseKey }),
    });
    return data;
  }

  // ── Email / Password sign-in ───────────────────────────────

  document.getElementById('wjc-email-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email    = document.getElementById('wjc-input-email').value.trim();
    const password = document.getElementById('wjc-input-password').value;
    const btn      = document.getElementById('wjc-signin-btn');

    showError('');
    setLoading(btn, true, 'Sign in');

    try {
      // 1. Authenticate with WP Jarvis backend.
      const authRes = await fetch(WJC.backendUrl + '/api/auth/password/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, domain: window.location.hostname }),
      }).then((r) => r.json());

      if (!authRes.success || !authRes.license_key) {
        throw new Error(authRes.error || 'Could not sign in. Check your credentials.');
      }

      // 2. Save credentials + generate Application Password.
      await connectWithCredentials(authRes.email || email, authRes.license_key);

      populateConnected(authRes.email || email);
      showScreen('connected');
    } catch (err) {
      showError(err.message || 'Sign in failed.');
    } finally {
      setLoading(btn, false, 'Sign in');
    }
  });

  // ── Google OAuth ───────────────────────────────────────────

  document.getElementById('wjc-google-btn')?.addEventListener('click', async () => {
    const btn = document.getElementById('wjc-google-btn');
    setLoading(btn, true, 'Continue with Google');
    showError('');

    try {
      const callback = encodeURIComponent(window.location.href.split('?')[0] + '?page=wp-jarvis-connector&wjc_google=1');
      const url = WJC.backendUrl + '/api/auth/google/start'
        + '?plugin_callback=' + callback
        + '&domain=' + encodeURIComponent(window.location.hostname);
      window.location.href = url;
    } catch (err) {
      showError(err.message || 'Could not start Google sign-in.');
      setLoading(btn, false, 'Continue with Google');
    }
  });

  // Handle Google callback (token in URL after redirect).
  const params = new URLSearchParams(window.location.search);
  const googleToken = params.get('wpjarvis_google_token');
  if (googleToken) {
    // Remove token from URL.
    const cleanUrl = window.location.href
      .replace(/[?&]wpjarvis_google_token=[^&]+/, '')
      .replace(/[?&]wjc_google=1/, '');
    window.history.replaceState(null, '', cleanUrl);

    // Exchange token with WP Jarvis backend.
    (async () => {
      try {
        const authRes = await fetch(WJC.backendUrl + '/api/auth/google/exchange', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ token: googleToken }),
        }).then((r) => r.json());

        if (!authRes.success || !authRes.license_key) {
          throw new Error(authRes.error || 'Google sign-in could not be completed.');
        }

        await connectWithCredentials(authRes.email, authRes.license_key);
        populateConnected(authRes.email);
        showScreen('connected');
      } catch (err) {
        showError(err.message || 'Google sign-in failed. Please try again.');
        showScreen('auth');
      }
    })();
  }

  // ── Disconnect ─────────────────────────────────────────────

  document.getElementById('wjc-disconnect-btn')?.addEventListener('click', async () => {
    if (!confirm('Disconnect WP Jarvis from this site?')) return;
    try {
      await api('/disconnect', { method: 'POST' });
      showScreen('auth');
    } catch (err) {
      alert(err.message || 'Could not disconnect.');
    }
  });

  // ── Open Builder ───────────────────────────────────────────

  document.getElementById('wjc-open-builder')?.addEventListener('click', (e) => {
    e.preventDefault();
    window.open(WJC.builderUrl, '_blank', 'noopener');
  });

  // ── Forgot password ────────────────────────────────────────

  document.getElementById('wjc-forgot-link')?.addEventListener('click', async (e) => {
    e.preventDefault();
    const email = document.getElementById('wjc-input-email').value.trim();
    if (!email) {
      showError('Enter your email address first.');
      return;
    }
    try {
      await fetch(WJC.backendUrl + '/api/auth/password/request-reset', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, domain: window.location.hostname }),
      });
      showSuccess('If that account exists, a reset link has been sent to your email.');
    } catch {
      showError('Could not send reset email. Please try again.');
    }
  });

  // ── Init ───────────────────────────────────────────────────

  if (connected) {
    populateConnected(WJC.email);
    showScreen('connected');
  } else {
    showScreen('auth');
  }

})();
