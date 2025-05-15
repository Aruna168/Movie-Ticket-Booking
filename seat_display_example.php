<?php
require_once 'display_seats.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat Display Example</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/seat_display.css">
</head>
<body class="bg-dark text-light">
    <div class="container py-5">
        <h1 class="mb-4">Seat Display Example</h1>
        
        <div class="card bg-secondary">
            <div class="card-header">
                <h3>Thiru.Manickam - Kalaiarangam - 2025-05-15 at 18:00:00</h3>
            </div>
            <div class="card-body">
                <?php
                // Example data
                $all_seats = "A1, A2, A3, A4, A5, A6, A7, A8, A9, A10, B1, B2, B3, B4, B5, B6, B7, B8, B9, B10, C1, C2, C3, C4, C5, C6, C7, C8, C9, C10, D1, D2, D3, D4, D5, D6, D7, D8, D9, D10, E1, E2, E3, E4, E5, E6, E7, E8, E9, E10, F1, F2, F3, F4, F5, F6, F7, F8, F9, F10, G1, G2, G3, G4, G5, G6, G7, G8, G9, G10, H1, H2, H3, H4, H5, H6, H7, H8, H9, H10";
                $booked_seats = "A3, B5, C7, D2, E9, F4, G6, H8";
                $selected_seats = "C4, C5, C6";
                
                // Display seats in grid layout
                echo displaySeatsGrid($all_seats, $booked_seats, $selected_seats);
                ?>
            </div>
        </div>
        
        <div class="mt-4">
            <h3>Interactive Example</h3>
            <p>Click on available seats to select/deselect them:</p>
            
            <div class="card bg-secondary">
                <div class="card-body">
                    <div id="interactive-seat-map">
                        <?php
                        // Display interactive seat map
                        echo displaySeatsGrid($all_seats, $booked_seats, []);
                        ?>
                    </div>
                    
                    <div class="mt-4">
                        <p>Selected Seats: <span id="selected-seats">None</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const interactiveSeatMap = document.querySelector('#interactive-seat-map');
            const selectedSeatsDisplay = document.querySelector('#selected-seats');
            const selectedSeats = [];
            
            // Add click event to available seats
            interactiveSeatMap.querySelectorAll('.seat.available').forEach(seat => {
                seat.addEventListener('click', function() {
                    const seatId = this.getAttribute('data-seat');
                    
                    if (this.classList.contains('selected')) {
                        // Deselect seat
                        this.classList.remove('selected');
                        this.classList.add('available');
                        
                        // Remove from selected seats array
                        const index = selectedSeats.indexOf(seatId);
                        if (index > -1) {
                            selectedSeats.splice(index, 1);
                        }
                    } else {
                        // Select seat
                        this.classList.remove('available');
                        this.classList.add('selected');
                        
                        // Add to selected seats array
                        selectedSeats.push(seatId);
                    }
                    
                    // Update display
                    if (selectedSeats.length > 0) {
                        selectedSeatsDisplay.textContent = selectedSeats.join(', ');
                    } else {
                        selectedSeatsDisplay.textContent = 'None';
                    }
                });
            });
        });
    </script>
</body>
</html>