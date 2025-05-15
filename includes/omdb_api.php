<?php
/**
 * Simple OMDb API wrapper
 */
class OMDbAPI {
    private $apiKey;
    private $baseUrl = 'http://www.omdbapi.com/';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Search for movies by title
     */
    public function searchMovies($title, $page = 1) {
        $params = [
            'apikey' => $this->apiKey,
            's' => $title,
            'page' => $page
        ];
        
        return $this->makeRequest($params);
    }
    
    /**
     * Get detailed information about a movie by IMDb ID
     */
    public function getMovieById($imdbId) {
        $params = [
            'apikey' => $this->apiKey,
            'i' => $imdbId,
            'plot' => 'full'
        ];
        
        return $this->makeRequest($params);
    }
    
    /**
     * Get detailed information about a movie by title
     */
    public function getMovieByTitle($title, $year = null) {
        $params = [
            'apikey' => $this->apiKey,
            't' => $title,
            'plot' => 'full'
        ];
        
        if ($year) {
            $params['y'] = $year;
        }
        
        return $this->makeRequest($params);
    }
    
    /**
     * Make API request
     */
    private function makeRequest($params) {
        $url = $this->baseUrl . '?' . http_build_query($params);
        
        $response = @file_get_contents($url);
        if ($response === false) {
            return ['Response' => 'False', 'Error' => 'Failed to connect to OMDb API'];
        }
        
        return json_decode($response, true);
    }
}
?>