function handleToggle(toggleBox, circle, checkbox) {
    if (!toggleBox || !circle || !checkbox) return;
    
    toggleBox.onclick = function() {
        if (checkbox.checked) {
            circle.style.transform = "translateX(25px)";
            circle.style.backgroundColor = "#251d3c";
            toggleBox.style.backgroundColor = "#352b55c2";
        } else {
            circle.style.transform = "translateX(0px)";
            circle.style.backgroundColor = "lightgray";
            toggleBox.style.backgroundColor = "#fff";
        }
    };
}

document.addEventListener('DOMContentLoaded', function() {
    handleToggle(
        document.querySelector(".check1"),
        document.querySelector(".circle1"),
        document.getElementById("newsletter_register")
    );
    
    handleToggle(
        document.querySelector(".check2"),
        document.querySelector(".circle2"),
        document.getElementById("terms")
    );
});