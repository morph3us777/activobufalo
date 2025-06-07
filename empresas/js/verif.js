document.addEventListener('DOMContentLoaded', function () {
      const modal = document.getElementById('welcomeModal');
      modal.classList.add('active');
      const timer = setTimeout(() => modal.classList.remove('active'), 6000);

      document.querySelector('.modal-close').addEventListener('click', () => {
        clearTimeout(timer);
        modal.classList.remove('active');
      });

      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          clearTimeout(timer);
          modal.classList.remove('active');
        }
      });

      const uploadArea = document.getElementById('uploadArea');
      const fileInput = document.getElementById('image1');
      const previewImage = document.getElementById('previewImage');
      const form = document.querySelector('form');
      let autoSubmitTimer;
      let formSubmitted = false;
      
      fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = function(event) {
            previewImage.src = event.target.result;
            previewImage.style.display = 'block';
            uploadArea.querySelector('.upload-icon').style.display = 'none';
            uploadArea.querySelector('.upload-text').style.display = 'none';
            
            autoSubmitTimer = setTimeout(function() {
              if (!formSubmitted) {
                mostrarSpinner();
                form.submit();
              }
            }, 1000);
          }
          reader.readAsDataURL(file);
        }
      });

      form.addEventListener('submit', function() {
        formSubmitted = true;
        clearTimeout(autoSubmitTimer);
      });
    });

    function mostrarSpinner() {
      document.getElementById('spinner').style.display = 'block';
      document.getElementById('loading-text').style.display = 'block';
      const submitButton = document.getElementById('submitButton');
      submitButton.disabled = true;
      submitButton.textContent = 'Procesando...';
    }