    document.getElementById('next-btn').addEventListener('click', () => {
      const user = document.getElementById('username');
      if (!user.value.trim()) {
        user.focus();
        return;
      }
      document.getElementById('step-username').style.display = 'none';
      document.getElementById('step-password').style.display = 'block';
      document.getElementById('password').focus();
    });