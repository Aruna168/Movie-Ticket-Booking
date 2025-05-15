<?php
// Function to display seats in a grid layout
function displaySeatsGrid($seats_data, $booked_seats = [], $selected_seats = []) {
    // Parse seats data
    $rows = [];
    $cols = [];
    
    // Example format: "A1, A2, B3, C4"
    if (is_string($seats_data)) {
        $seats_array = array_map('trim', explode(',', $seats_data));
        foreach ($seats_array as $seat) {
            if (preg_match('/([A-Z])(\d+)/', $seat, $matches)) {
                $row = $matches[1];
                $col = (int)$matches[2];
                
                if (!in_array($row, $rows)) {
                    $rows[] = $row;
                }
                if (!in_array($col, $cols)) {
                    $cols[] = $col;
                }
            }
        }
        
        // Sort rows and columns
        sort($rows);
        sort($cols);
    } 
    // If it's already an array with row and column information
    else if (is_array($seats_data)) {
        if (isset($seats_data['rows']) && isset($seats_data['cols'])) {
            $rows = $seats_data['rows'];
            $cols = $seats_data['cols'];
        }
    }
    
    // If no valid data, use default layout
    if (empty($rows) || empty($cols)) {
        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $cols = range(1, 10);
    }
    
    // Convert booked and selected seats to arrays if they're strings
    if (is_string($booked_seats)) {
        $booked_seats = array_map('trim', explode(',', $booked_seats));
    }
    
    if (is_string($selected_seats)) {
        $selected_seats = array_map('trim', explode(',', $selected_seats));
    }
    
    // Start building the HTML
    $html = '<div class="seat-map-container">';
    $html .= '<div class="screen">SCREEN</div>';
    $html .= '<div class="seat-grid">';
    
    foreach ($rows as $row) {
        $html .= '<div class="seat-row">';
        $html .= '<div class="row-label">' . $row . '</div>';
        
        foreach ($cols as $col) {
            $seat_id = $row . $col;
            $seat_class = 'seat available';
            
            if (in_array($seat_id, $booked_seats)) {
                $seat_class = 'seat booked';
            } elseif (in_array($seat_id, $selected_seats)) {
                $seat_class = 'seat selected';
            }
            
            $html .= '<div class="' . $seat_class . '" data-seat="' . $seat_id . '">' . $col . '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // Add legend
    $html .= '<div class="seat-legend">';
    $html .= '<div class="legend-item"><div class="legend-box available"></div><span>Available</span></div>';
    $html .= '<div class="legend-item"><div class="legend-box booked"></div><span>Booked</span></div>';
    $html .= '<div class="legend-item"><div class="legend-box selected"></div><span>Selected</span></div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}
?>