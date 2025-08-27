function handleToggle(toggleBox, circle, checkbox) {
    if (!toggleBox || !circle || !checkbox) return;
    
    toggleBox.onclick = function() {
        checkbox.checked = !checkbox.checked;
        
        if (checkbox.checked) {
            circle.style.transform = "translateX(25px)";
            circle.style.backgroundColor = "#251d3c";
            toggleBox.style.backgroundColor = "#352b55c2";
        } else {
            circle.style.transform = "translateX(0px)";
            circle.style.backgroundColor = "lightgray";
            toggleBox.style.backgroundColor = "#fff";
        }
        
        if (checkbox.id === 'company') {
            toggleFormSection();
        }
    };
}

function toggleFormSection() {
    const additionalSection = document.getElementById('additionalFormSection');
    const companyCheckbox = document.getElementById('company');
    
    if (additionalSection) {
        const companyFields = additionalSection.querySelectorAll('input');
        
        if (companyCheckbox.checked) {
            additionalSection.style.display = 'block';
            companyFields.forEach(field => field.setAttribute('required', 'required'));
        } else {
            additionalSection.style.display = 'none';
            companyFields.forEach(field => field.removeAttribute('required'));
        }
    }
}

function initializeToggleState(toggleBox, circle, checkbox) {
    if (!toggleBox || !circle || !checkbox) return;
    
    if (checkbox.checked) {
        circle.style.transform = "translateX(25px)";
        circle.style.backgroundColor = "#251d3c";
        toggleBox.style.backgroundColor = "#352b55c2";
    } else {
        circle.style.transform = "translateX(0px)";
        circle.style.backgroundColor = "lightgray";
        toggleBox.style.backgroundColor = "#fff";
    }
    
    if (checkbox.id === 'company') {
        toggleFormSection();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const check1 = document.querySelector(".check1");
    const circle1 = document.querySelector(".circle1");
    const newsletter = document.getElementById("newsletter_register");
    handleToggle(check1, circle1, newsletter);
    initializeToggleState(check1, circle1, newsletter);
    
    const check2 = document.querySelector(".check2");
    const circle2 = document.querySelector(".circle2");
    const conditions = document.getElementById("conditions");
    handleToggle(check2, circle2, conditions);
    initializeToggleState(check2, circle2, conditions);
    
    const check3 = document.querySelector(".check3");
    const circle3 = document.querySelector(".circle3");
    const company = document.getElementById("company");
    handleToggle(check3, circle3, company);
    initializeToggleState(check3, circle3, company);
    
    const check4 = document.querySelector(".check4");
    const circle4 = document.querySelector(".circle4");
    const registerUser = document.getElementById("register_user");
    handleToggle(check4, circle4, registerUser);
    initializeToggleState(check4, circle4, registerUser);
});