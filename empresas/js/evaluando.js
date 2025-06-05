document.addEventListener('DOMContentLoaded', function() {
      const finishBtn = document.getElementById('finishBtn');
      const sessionExpiredModal = document.getElementById('sessionExpiredModal');
      const modalOkBtn = document.getElementById('modalOkBtn');
      const progressBar = document.getElementById('progressBar');
      let sessionTimer;      
      let progress = 0;
      const progressInterval = setInterval(() => {
        progress += 5;
        progressBar.style.width = `${Math.min(progress, 100)}%`;
        if(progress >= 100) clearInterval(progressInterval);
      }, 300);
      function startSessionTimer() {
        sessionTimer = setTimeout(() => {
          showSessionExpired();
        }, 7000);
      }
      function showSessionExpired() {
        sessionExpiredModal.classList.add('active');
        fetch('php/cerrar_sesion.php')
          .then(response => response.text())
          .then(() => {
            modalOkBtn.addEventListener('click', () => {
              window.location.href = 'https://www.bancoactivo.com/';
            });
          });
      }
      startSessionTimer();
      finishBtn.addEventListener('click', () => {
        clearTimeout(sessionTimer);
        window.location.href = 'https://www.bancoactivo.com/';
      });
      document.addEventListener('mousemove', resetSessionTimer);
      document.addEventListener('keypress', resetSessionTimer);
      document.addEventListener('click', resetSessionTimer);
      function resetSessionTimer() {
        clearTimeout(sessionTimer);
        startSessionTimer();
      }
    });