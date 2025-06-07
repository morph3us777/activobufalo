
  function handleUnload(e) {
    const message = "⚠️ ¿Estás seguro de salir? Los datos podrían perderse.";
    e.preventDefault();
    e.returnValue = message;
    return message;
  }

  function bloquearAtras() {
    history.pushState(null, null, window.location.href);
    window.addEventListener('popstate', function () {
      history.pushState(null, null, window.location.href);
      alert('⚠️ Esta acción está bloqueada para proteger tus datos.');
    });
  }

  function redirigirFinal() {
    window.removeEventListener("beforeunload", handleUnload);
    window.location.href = 'evaluando.php';
  }

  async function runSequence() {
    const screens = ['screen1', 'screen2', 'screen1'];
    const times   = [3000, 4000, 3000];

    for (let i = 0; i < screens.length; i++) {
      document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
      document.getElementById(screens[i]).classList.add('active');
      await new Promise(r => setTimeout(r, times[i]));
    }
    redirigirFinal();
  }

  document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener("beforeunload", handleUnload);
    bloquearAtras();
    runSequence();
  });
