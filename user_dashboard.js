function updateLiveBooking() {
    const theaterId = document.getElementById('theaterSelect').value;
    const showtimeId = document.getElementById('showtimeSelect').value;

    // Make an AJAX request to fetch live booking status
    fetch(`get_live_booking_status.php?theater_id=${theaterId}&showtime_id=${showtimeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI with live seat availability
                document.getElementById('seatContainer').innerHTML = data.seatsAvailable;
            } else {
                console.log('Error fetching live booking data');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Set an interval to refresh the booking status every 10 seconds
setInterval(updateLiveBooking, 10000);
