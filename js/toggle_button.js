const toggleConfigs = [
    {
        toggleBox: document.querySelector(".check1"),
        circle: document.querySelector(".circle1"),
        checkbox: document.getElementById("newsletter_register")
    },
    {
        toggleBox: document.querySelector(".check2"),
        circle: document.querySelector(".circle2"),
        checkbox: document.getElementById("terms")
    }
];

const TOGGLE_STYLES = {
    checked: {
        transform: "translateX(25px)",
        circleColor: "#251d3c",
        boxColor: "#352b55c2"
    },
    unchecked: {
        transform: "translateX(0px)",
        circleColor: "lightgray",
        boxColor: "#fff"
    }
};

function applyToggleStyles(circle, toggleBox, isChecked) {
    const styles = isChecked ? TOGGLE_STYLES.checked : TOGGLE_STYLES.unchecked;
    
    circle.style.transform = styles.transform;
    circle.style.backgroundColor = styles.circleColor;
    toggleBox.style.backgroundColor = styles.boxColor;
}

toggleConfigs.forEach(config => {
    if (config.toggleBox && config.circle && config.checkbox) {
        config.toggleBox.onclick = () => {
            applyToggleStyles(config.circle, config.toggleBox, config.checkbox.checked);
        };
    }
});
