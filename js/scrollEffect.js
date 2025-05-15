document.addEventListener("DOMContentLoaded", function () {
    let elements = document.querySelectorAll(".movie-card");

    const observer = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.style.transform = "perspective(1000px) rotateY(0deg)";
                    entry.target.style.opacity = "1";
                } else {
                    entry.target.style.transform = "perspective(1000px) rotateY(-10deg)";
                    entry.target.style.opacity = "0.5";
                }
            });
        },
        { threshold: 0.2 }
    );

    elements.forEach((el) => {
        observer.observe(el);
    });
});
