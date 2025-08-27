document.addEventListener('DOMContentLoaded', function() {
    function updateTotalPriceAndCartCount() {
        let rows = document.querySelectorAll('.end-price');
        let totalCartCount = 0; 
        let totalCartValue = 0; 

        rows.forEach(function(row, index) {
            let quantityInput = row.closest('tr').querySelector('input[name="quantity"]');
            let priceText = row.closest('tr').querySelector('td:nth-child(5)').textContent;

            let quantity = parseInt(quantityInput.value);
            let price = parseFloat(priceText.replace(' Kč/ks', '').replace(',', '.'));

            let totalPrice = (quantity * price).toFixed(2);

            row.textContent = totalPrice + ' Kč';

            localStorage.setItem('totalPrice_' + index, totalPrice);
            localStorage.setItem('quantity_' + index, quantity);

            totalCartCount += quantity;
            totalCartValue += parseFloat(totalPrice);
        });

        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = totalCartCount;
        }
        localStorage.setItem('totalCartCount', totalCartCount);

    }

    function loadTotalPricesAndQuantities() {
        let rows = document.querySelectorAll('.end-price');
        let totalCartCount = 0;

        rows.forEach(function(row, index) {
            let storedPrice = localStorage.getItem('totalPrice_' + index);
            let storedQuantity = localStorage.getItem('quantity_' + index);

            if (storedPrice) {
                row.textContent = storedPrice + ' Kč';
            }

            if (storedQuantity) {
                let quantityInput = row.closest('tr').querySelector('input[name="quantity"]');
                if (quantityInput) {
                    quantityInput.value = storedQuantity;
                    totalCartCount += parseInt(storedQuantity);
                }
            }
        });

        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = totalCartCount;
        }
    }

    function updateProgressBar(totalCartValue) {
        const freeShippingLimit = 1000;
        const progressBar = document.getElementById("progress");
        const progressInfo = document.getElementById("progress-info");
        
        if (!progressBar || !progressInfo) {
            return;
        }
        
        let progress = (totalCartValue / freeShippingLimit) * 100;

        if (progress > 100) {
            progress = 100;
            progressInfo.textContent = "Máte dopravu zdarma!";
        } else {
            progressInfo.textContent = `Zbývá ${(freeShippingLimit - totalCartValue).toFixed(2)} Kč do dopravy zdarma`;
        }

        progressBar.style.width = progress + "%";
    }

    loadTotalPricesAndQuantities();

    document.querySelectorAll('.quantity-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            updateTotalPriceAndCartCount();
        });
    });
});


document.addEventListener('DOMContentLoaded', function() {
    let totalCartValue = 0; 
    const freeShippingLimit = 1500; 
    const progressBar = document.getElementById("progress");
    const progressInfo = document.getElementById("progress-info");

    function updateProgressBar() {
        if (!progressBar || !progressInfo) {
            return;
        }
        
        let progress = (totalCartValue / freeShippingLimit) * 100;
        
        if (progress > 100) {
            progress = 100;
            progressInfo.textContent = "Máte dopravu zdarma!";
        } else {
            progressInfo.textContent = `Zbývá ${(freeShippingLimit - totalCartValue).toFixed(2)} Kč do dopravy zdarma`;
        }
        
        progressBar.style.width = progress + "%";
    }

    totalCartValue = 300; 
    updateProgressBar();
});

