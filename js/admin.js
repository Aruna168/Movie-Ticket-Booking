document.addEventListener("DOMContentLoaded", function () {
    const theaterSelect = document.getElementById("theaterSelect");
    const showtimeSelect = document.getElementById("showtimeSelect");
    const searchBar = document.getElementById("searchBar");
    const seatContainer = document.getElementById("seatContainer");

    // Function to Fetch Seat Data
    function fetchSeats() {
        const theaterId = theaterSelect.value;
        const showtimeId = showtimeSelect.value;
        const query = searchBar.value;

        // Fetch seat data from backend (Replace 'fetch_seats.php' with your API)
        fetch(`fetch_seats.php?theater=${theaterId}&showtime=${showtimeId}&query=${query}`)
            .then(response => response.json())
            .then(data => {
                seatContainer.innerHTML = ""; // Clear previous seats

                data.forEach((seat, index) => {
                    let seatDiv = document.createElement("div");
                    seatDiv.classList.add("seat");
                    seatDiv.textContent = index + 1;

                    if (seat.status === "booked") {
                        seatDiv.classList.add("booked"); // Mark seat as booked
                    }

                    seatContainer.appendChild(seatDiv);
                });
            })
            .catch(error => console.error("Error fetching seats:", error));
    }

    // Event Listeners for Dynamic Updates
    theaterSelect.addEventListener("change", fetchSeats);
    showtimeSelect.addEventListener("change", fetchSeats);
    searchBar.addEventListener("input", fetchSeats);

    // Auto Fetch on Page Load
    fetchSeats();
});
