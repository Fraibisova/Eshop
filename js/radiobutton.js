function selectRadio(id) {
    const element = document.getElementById(id);
    if (!element) return;
    
    const parentPay = element.closest('.radio-group-pay');
    const parentShipping = element.closest('.radio-group-shipping');

    if (parentPay) {
        document.querySelectorAll('.radio-group-pay').forEach(group => {
            group.classList.remove('selected');
        });
        parentPay.classList.add('selected');
        localStorage.setItem('selectedRadioPay', id);
    } else if (parentShipping) {
        document.querySelectorAll('.radio-group-shipping').forEach(group => {
            group.classList.remove('selected');
        });
        parentShipping.classList.add('selected');
        localStorage.setItem('selectedRadioShipping', id);
    }

    element.checked = true;
}

function restoreSelection(storageKey, selector) {
    const selectedId = localStorage.getItem(storageKey);
    if (selectedId) {
        const element = document.getElementById(selectedId);
        if (element) {
            element.checked = true;
            selectRadio(selectedId);
        }
    }
}

window.onload = function() {
    restoreSelection('selectedRadioPay');
    restoreSelection('selectedRadioShipping');
};
