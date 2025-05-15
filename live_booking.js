document.addEventListener("DOMContentLoaded", function () {
    const theaterSelect = document.getElementById("theaterSelect");
    const showtimeSelect = document.getElementById("showtimeSelect");
    const searchBar = document.getElementById("searchBar");
    const seatContainer = document.getElementById("seatContainer");

    function fetchSeats() {
        const theaterId = theaterSelect.value;
        const showtimeId = showtimeSelect.value;
        const query = searchBar.value;

        fetch(`fetch_seats.php?theater=${theaterId}&showtime=${showtimeId}&query=${query}`)
            .then(response => response.json())
            .then(data => {
                seatContainer.innerHTML = "";
                data.forEach((seat, index) => {
                    let seatDiv = document.createElement("div");
                    seatDiv.classList.add("seat");
                    seatDiv.textContent = index + 1;
                    if (seat.status === "booked") {
                        seatDiv.classList.add("booked");
                    }
                    seatContainer.appendChild(seatDiv);
                });
            })
            .catch(error => console.error("Error fetching seat data:", error));
    }

    theaterSelect.addEventListener("change", fetchSeats);
    showtimeSelect.addEventListener("change", fetchSeats);
    searchBar.addEventListener("input", fetchSeats);

    setInterval(fetchSeats, 5000); // Auto-refresh every 5 seconds
    fetchSeats();
});
