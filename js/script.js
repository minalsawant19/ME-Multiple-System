document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.actionBtn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();

            let currentBox = this.closest('td').querySelector('.actionBox');

            // sab actionBox band karo (except current)
            document.querySelectorAll('.actionBox').forEach(box => {
                if (box !== currentBox) {
                    box.style.display = "none";
                }
            });

            // current toggle
            currentBox.style.display =
                (currentBox.style.display === "block") ? "none" : "block";
        });
    });

    // bahar click pe sab band
    document.addEventListener('click', function () {
        document.querySelectorAll('.actionBox').forEach(box => {
            box.style.display = "none";
        });
    });

});

document.querySelectorAll(".tab").forEach(tab => {
    tab.addEventListener("click", () => {

        document.querySelector(".tab.active").classList.remove("active");
        tab.classList.add("active");

        let target = tab.getAttribute("data-target");

        document.querySelector(".content.active").classList.remove("active");
        document.getElementById(target).classList.add("active");
    });
});