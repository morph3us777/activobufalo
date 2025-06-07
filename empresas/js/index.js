    document.getElementById('next-btn').addEventListener('click', () => {
      const dt = document.getElementById('doctype'),
            rf = document.getElementById('rif'),
            us = document.getElementById('username');
      if (!dt.value || !rf.value.trim() || !us.value.trim()) {
        const el = !dt.value ? dt : !rf.value.trim() ? rf : us;
        el.focus(); return;
      }
      document.getElementById('step-credentials').style.display = 'none';
      document.getElementById('step-password').style.display = 'block';
      document.getElementById('password').focus();
    });