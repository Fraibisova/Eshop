function isMobile() {
    return /android|iphone|ipad|ipod|blackberry|windows phone/i.test(navigator.userAgent.toLowerCase()) || window.innerWidth <= 768;
}
document.addEventListener("DOMContentLoaded", () => {
    if (isMobile()) {
        const loader = document.querySelector(".loader");
        if (loader) {
            loader.remove();
        }
    }
});


function showLoader() {
    if (isMobile()) {
        return;
    }
    
    const loader = document.querySelector(".loader");
    if (loader) {
        loader.classList.remove("loader--hidden");
        loader.style.display = "block"; 
    }
}

function hideLoader() {
    if (isMobile()) {
        return;
    }
    
    const loader = document.querySelector(".loader");
    if (!loader) return;

    loader.classList.add("loader--hidden");
    
    loader.addEventListener("transitionend", () => {
        if (loader.parentNode) {
            loader.style.display = "none";
        }
    }, { once: true });
}

window.addEventListener("load", () => {
    hideLoader();
});

async function fetchDataWithLoader() {
    showLoader();
    try {
        const response = await fetch('/api/data');
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Chyba:', error);
    } finally {
        hideLoader(); 
    }
}

function heavyCalculationWithLoader() {
    showLoader();
    
    setTimeout(() => {
        
        hideLoader();
    }, 10); 
}

function submitFormWithLoader(formData) {
    showLoader();
    
    fetch('/submit', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Úspěch:', data);
    })
    .catch(error => {
        console.error('Chyba:', error);
    })
    .finally(() => {
        hideLoader();
    });
}

function updateUIWithLoader() {
    showLoader();
    
    requestAnimationFrame(() => {
        document.querySelectorAll('.item').forEach(item => {
        });
        
        hideLoader();
    });
}

async function withLoader(asyncOperation) {
    showLoader();
    try {
        const result = await asyncOperation();
        return result;
    } finally {
        hideLoader();
    }
}

