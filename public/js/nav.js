document.addEventListener("DOMContentLoaded", function () {
    var modal = document.getElementById("loginModal");
    var btn = document.getElementById("loginBtn");
    var span = document.getElementsByClassName("close")[0];

    if (btn && modal && span) {
        btn.onclick = function () {
            modal.style.display = "block";
        }

        span.onclick = function () {
            modal.style.display = "none";
        }

        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    }

    let lastScrollTop = 0;
    const navbar = document.getElementById('navbar');

    window.addEventListener('scroll', function () {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        let quarterPageHeight = document.documentElement.scrollHeight / 4;

        if (scrollTop > lastScrollTop && scrollTop > quarterPageHeight) {
            navbar?.classList.add('hidden');
            modal?.classList.add('hidden');
            if (modal) modal.style.display = "none";
        } else if (scrollTop < lastScrollTop) {
            navbar?.classList.remove('hidden');
        }

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });

    const hamburger = document.querySelector('.hamburger');
    const navmenu = document.querySelector('.nav-menu');

    hamburger?.addEventListener("click", () => {
        hamburger.classList.toggle("active");
        navmenu?.classList.toggle("active");
    });

    document.querySelectorAll(".nav-link").forEach(n => {
        n.addEventListener("click", () => {
            hamburger?.classList.remove("active");
            navmenu?.classList.remove("active");
        });
    });
});