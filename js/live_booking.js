// Configuration
const rows = 8;
const seatsPerRow = 10;
const rowLabels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
let currentShowId = null;
let refreshInterval = null;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Load theaters for dropdown
    loadTheaters();
    
    // Add event listeners
    document.getElementById('theaterSelect').addEventListener('change', loadShowtimes);
    document.getElementById('showtimeSelect').addEventListener('change', loadSeatMatrix);
    document.getElementById('searchBar').addEventListener('input', filterShowtimes);
});

// Load theaters for dropdown
function loadTheaters() {
    fetch('get_theaters.php')
        .then(response => response.json())
        .then(theaters => {
            const theaterSelect = document.getElementById('theaterSelect');
            theaterSelect.innerHTML = '<option value="">Select Theater</option>';
            
            theaters.forEach(theater => {
                const option = document.createElement('option');
                option.value = theater.theater_id;
                option.textContent = theater.name;
                theaterSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading theaters:', error));
}

// Load showtimes for selected theater
function loadShowtimes() {
    const theaterId = document.getElementById('theaterSelect').value;
    if (!theaterId) {
        document.getElementById('showtimeSelect').innerHTML = '<option value="">Select Showtime</option>';
        document.getElementById('seatContainer').innerHTML = '';
        return;
    }
    
    fetch(`get_showtimes.php?theater_id=${theaterId}`)
        .then(response => response.json())
        .then(showtimes => {
            const showtimeSelect = document.getElementById('showtimeSelect');
            showtimeSelect.innerHTML = '<option value="">Select Showtime</option>';
            
            showtimes.forEach(showtime => {
                const option = document.createElement('option');
                option.value = showtime.show_id;
                option.textContent = `${showtime.title} - ${showtime.show_date} ${showtime.show_time}`;
                option.dataset.title = showtime.title;
                option.dataset.date = showtime.show_date;
                option.dataset.time = showtime.show_time;
                showtimeSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error loading showtimes:', error));
}

// Filter showtimes based on search input
function filterShowtimes() {
    const searchTerm = document.getElementById('searchBar').value.toLowerCase();
    const options = document.getElementById('showtimeSelect').options;
    
    for (let i = 1; i < options.length; i++) {
        const option = options[i];
        const text = option.textContent.toLowerCase();
        
        if (text.includes(searchTerm)) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }
}

// Load seat matrix for selected showtime
function loadSeatMatrix() {
    const showId = document.getElementById('showtimeSelect').value;
    if (!showId) {
        document.getElementById('seatContainer').innerHTML = '';
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
        return;
    }
    
    currentShowId = showId;
    updateSeatMatrix();
    
    // Set up auto-refresh
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    refreshInterval = setInterval(updateSeatMatrix, 5000); // Refresh every 5 seconds
}

// Update seat matrix with latest booking data
function updateSeatMatrix() {
    if (!currentShowId) return;
    
    fetch(`get_live_booking_status.php?show_id=${currentShowId}`)
        .then(response => response.json())
        .then(data => {
            renderSeatMatrix(data.occupied_seats, data.pending_seats, data.show_details);
        })
        .catch(error => console.error('Error updating seat matrix:', error));
}

// Render the seat matrix
function renderSeatMatrix(occupiedSeats, pendingSeats, showDetails) {
    const seatContainer = document.getElementById('seatContainer');
    seatContainer.innerHTML = '';
    
    // Add theater info if available
    if (showDetails) {
        const theaterInfo = document.createElement('div');
        theaterInfo.className = 'theater-info';
        theaterInfo.innerHTML = `
            <h4>${showDetails.title}</h4>
            <p>${showDetails.theater_name} - ${showDetails.show_date} at ${showDetails.show_time}</p>
        `;
        seatContainer.appendChild(theaterInfo);
    }
    
    // Create admin seat matrix
    const matrixDiv = document.createElement('div');
    matrixDiv.className = 'admin-seat-matrix';
    
    // Add screen
    const screenDiv = document.createElement('div');
    screenDiv.className = 'screen';
    matrixDiv.appendChild(screenDiv);
    
    // Create seat rows
    for (let i = 0; i < rows; i++) {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'admin-seat-row';
        
        // Add row label
        const rowLabel = document.createElement('div');
        rowLabel.className = 'row-label';
        rowLabel.textContent = rowLabels[i];
        rowDiv.appendChild(rowLabel);
        
        for (let j = 0; j < seatsPerRow; j++) {
            const seat = document.createElement('div');
            const seatId = `${rowLabels[i]}${j + 1}`;
            
            seat.className = 'admin-seat';
            seat.textContent = j + 1;
            
            // Set seat status
            if (occupiedSeats.includes(seatId)) {
                seat.classList.add('booked');
                seat.title = `Seat ${seatId} - Booked`;
            } else if (pendingSeats.includes(seatId)) {
                seat.classList.add('selected');
                seat.title = `Seat ${seatId} - Being Selected`;
            } else {
                seat.classList.add('available');
                seat.title = `Seat ${seatId} - Available`;
            }
            
            rowDiv.appendChild(seat);
        }
        
        matrixDiv.appendChild(rowDiv);
    }
    
    // Add legend
    const legendDiv = document.createElement('div');
    legendDiv.className = 'seat-legend';
    legendDiv.innerHTML = `
        <div class="legend-item">
            <div class="legend-box available"></div>
            <span>Available</span>
        </div>
        <div class="legend-item">
            <div class="legend-box booked"></div>
            <span>Booked</span>
        </div>
        <div class="legend-item">
            <div class="legend-box selected"></div>
            <span>Being Selected</span>
        </div>
    `;
    matrixDiv.appendChild(legendDiv);
    
    // Add refresh button
    const refreshButton = document.createElement('button');
    refreshButton.className = 'refresh-button';
    refreshButton.textContent = 'Refresh Now';
    refreshButton.addEventListener('click', updateSeatMatrix);
    matrixDiv.appendChild(refreshButton);
    
    seatContainer.appendChild(matrixDiv);
    
    // Add booking statistics
    const statsDiv = document.createElement('div');
    statsDiv.className = 'booking-stats mt-4 text-center';
    
    const totalSeats = rows * seatsPerRow;
    const bookedSeats = occupiedSeats.length;
    const pendingBookings = pendingSeats.length;
    const availableSeats = totalSeats - bookedSeats - pendingBookings;
    const occupancyRate = Math.round((bookedSeats / totalSeats) * 100);
    
    statsDiv.innerHTML = `
        <div class="row text-white">
            <div class="col-md-3">
                <div class="stat-card bg-primary p-3 rounded">
                    <h5>Total Seats</h5>
                    <h3>${totalSeats}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success p-3 rounded">
                    <h5>Available</h5>
                    <h3>${availableSeats}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-danger p-3 rounded">
                    <h5>Booked</h5>
                    <h3>${bookedSeats}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning p-3 rounded">
                    <h5>Occupancy</h5>
                    <h3>${occupancyRate}%</h3>
                </div>
            </div>
        </div>
    `;
    
    seatContainer.appendChild(statsDiv);
}