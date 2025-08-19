document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('newsletterForm');
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    updatePreview();
                }, 500);
            });
        });
    });

    function updatePreview() {
        const form = document.getElementById('newsletterForm');
        const formData = new FormData(form);
        formData.append('action', 'preview');
        
        fetch('', {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                const iframe = document.querySelector('iframe');
                iframe.src = 'newsletter_preview.html?' + new Date().getTime();
            }
        }).catch(error => {
            console.error('Chyba při aktualizaci náhledu:', error);
        });
    }