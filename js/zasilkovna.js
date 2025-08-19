document.addEventListener('DOMContentLoaded', function() {
    const zasilkovnaRadio = document.getElementById('zasilkovna');
    const branchInfo = document.getElementById('branch-info');
    const branchName = document.getElementById('branch-name');
    const changeBranchBtn = document.getElementById('change-branch-btn');
    const zasilkovnaContainer = document.getElementById('zasilkovna-container');
    const finalPriceElement = document.getElementById('final-price');

    const shippingCost = 79; 
    let cartTotal = parseFloat(finalPriceElement.textContent.replace(/[^0-9.]/g, ''));
    function updateFinalPrice() {
        let newTotal = cartTotal;

        if (zasilkovnaRadio.checked) {
            newTotal += shippingCost;
        }

        finalPriceElement.textContent = `Celková cena: ${newTotal} Kč`;
        localStorage.setItem('finalPrice', newTotal); 
    }
    
    function openZasilkovnaWidget() {
        Packeta.Widget.pick(
            "dc92d690e3a152d6", 
            function(branch) {
                if (branch) {
                    localStorage.setItem('selectedBranchId', branch.id);
                    localStorage.setItem('selectedBranchName', branch.name);

                    document.getElementById('zasilkovna_branch').value = branch.id;
                    document.getElementById('zasilkovna_branch_name').value = branch.name;


                    branchName.textContent = branch.name;

                    branchInfo.style.display = 'block';
                    changeBranchBtn.style.display = 'block';
                }
            },
            {
                country: 'cz',
                language: 'cs'
            },
            null
        );
    }

    function resetBranchInfo() {
        document.getElementById('zasilkovna_branch').value = '';
        document.getElementById('zasilkovna_branch_name').value = '';
        branchName.textContent = '';  
        branchInfo.style.display = 'none';
        changeBranchBtn.style.display = 'none'; 
    }

    function loadSavedState() {
        const savedBranchId = localStorage.getItem('selectedBranchId');
        const savedBranchName = localStorage.getItem('selectedBranchName');
        const savedFinalPrice = localStorage.getItem('finalPrice'); 
    
        if (savedBranchId) {
            document.getElementById('select-branch-p')?.remove();
    
            zasilkovnaRadio.checked = true;
    
            if (savedFinalPrice) {
                finalPriceElement.textContent = `Celková cena: ${savedFinalPrice} Kč`;
                cartTotal = parseFloat(savedFinalPrice);
            }
    
            branchName.textContent = "Vybraná pobočka: " + savedBranchName;

            document.getElementById('zasilkovna_branch').value = savedBranchId;
            document.getElementById('zasilkovna_branch_name').value = savedBranchName;

            branchInfo.style.display = 'block';
            changeBranchBtn.style.display = 'block';
        } else {
            resetBranchInfo();
        }
    }
    

    zasilkovnaContainer.addEventListener('click', function() {
        zasilkovnaRadio.checked = true;
        openZasilkovnaWidget();
    });

    document.querySelectorAll('input[type=radio]').forEach(function(radio) {
        if (radio !== zasilkovnaRadio) {
            radio.addEventListener('change', function() {
                if (radio.checked) {
                    if (!zasilkovnaRadio.checked) {
                        resetBranchInfo();
                        localStorage.removeItem('selectedBranchId');
                        localStorage.removeItem('selectedBranchName');
                    }
                }

            });
        }
    });

    changeBranchBtn.addEventListener('click', function() {
        openZasilkovnaWidget();
    });
    zasilkovnaRadio.addEventListener('change', updateFinalPrice); 

    loadSavedState();
});
