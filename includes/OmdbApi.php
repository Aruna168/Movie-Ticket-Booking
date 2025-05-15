<?php
/**
 * OMDb API Handler Class
 * 
 * This class handles all interactions with the OMDb API
 */
class OmdbApi {
    private $apiKey;
    private $baseUrl = 'http://www.omdbapi.com/';
    private $cacheDir = 'cache/omdb/';
    private $cacheDuration = 86400; // 24 hours in seconds
    
    /**
     * Constructor
     * 
     * @param string $apiKey Your OMDb API key
     */
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        
        // Create cache directory if it doesn't exist
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }
    
    /**
     * Search for movies by title
     * 
     * @param string $title Movie title to search for
     * @param int $page Page number for results (default: 1)
     * @param string $type Type of result (movie, series, episode)
     * @param string $year Year of release
     * @return array Search results
     */
    public function searchMovies($title, $page = 1, $type = 'movie', $year = '') {
        $params = [
            's' => $title,
            'page' => $page,
            'type' => $type
        ];
        
        if (!empty($year)) {
            $params['y'] = $year;
        }
        
        return $this->makeRequest($params);
    }
    
    /**
     * Get detailed information about a movie by IMDb ID
     * 
     * @param string $imdbId IMDb ID of the movie
     * @param bool $fullPlot Whether to include the full plot (default: false)
     * @return array Movie details
     */
    public function getMovieById($imdbId, $fullPlot = false) {
        $params = [
            'i' => $imdbId,
            'plot' => $fullPlot ? 'full' : 'short'
        ];
        
        return $this->makeRequest($params);
    }
    
    /**
     * Get detailed information about a movie by title
     * 
     * @param string $title Title of the movie
     * @param string $year Year of release (optional)
     * @param bool $fullPlot Whether to include the full plot (default: false)
     * @return array Movie details
     */
    public function getMovieByTitle($title, $year = '', $fullPlot = false) {
        $params = [
            't' => $title,
            'plot' => $fullPlot ? 'full' : 'short'
        ];
        
        if (!empty($year)) {
            $params['y'] = $year;
        }
        
        return $this->makeRequest($params);
    }
    
    /**
     * Download a movie poster to local storage
     * 
     * @param string $posterUrl URL of the poster image
     * @param string $imdbId IMDb ID to use as filename
     * @return string|bool Local path to saved poster or false on failure
     */
    public function downloadPoster($posterUrl, $imdbId) {
        if (empty($posterUrl) || $posterUrl == 'N/A') {
            return false;
        }
        
        // Use the same upload directory as manual uploads
        $uploadDir = 'uploads/posters/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filename = 'omdb_' . $imdbId . '.jpg';
        $localPath = $uploadDir . $filename;
        
        // Download the image
        $posterData = @file_get_contents($posterUrl);
        if ($posterData === false) {
            return false;
        }
        
        // Save the image
        if (file_put_contents($localPath, $posterData)) {
            return $localPath;
        }
        
        return false;
    }
    
    /**
     * Make an API request with caching
     * 
     * @param array $params Request parameters
     * @return array Response data
     */
    private function makeRequest($params) {
        // Add API key to parameters
        $params['apikey'] = $this->apiKey;
        
        // Generate cache key
        $cacheKey = md5(serialize($params));
        $cachePath = $this->cacheDir . $cacheKey . '.json';
        
        // Check if we have a valid cached response
        if (file_exists($cachePath) && (time() - filemtime($cachePath) < $this->cacheDuration)) {
            $cachedData = file_get_contents($cachePath);
            return json_decode($cachedData, true);
        }
        
        // Build request URL
        $url = $this->baseUrl . '?' . http_build_query($params);
        
        // Make the request
        $response = @file_get_contents($url);
        
        if ($response === false) {
            // Handle request failure
            return ['Response' => 'False', 'Error' => 'Failed to connect to OMDb API'];
        }
        
        // Parse response
        $data = json_decode($response, true);
        
        // Cache the response
        if (isset($data['Response']) && $data['Response'] === 'True') {
            file_put_contents($cachePath, $response);
        }
        
        return $data;
    }
    
    /**
     * Format movie data for database storage
     * 
     * @param array $movieData Raw movie data from OMDb API
     * @return array Formatted movie data
     */
    public function formatMovieData($movieData) {
        // Default values
        $formatted = [
            'title' => '',
            'genre' => '',
            'duration' => 0,
            'release_date' => date('Y-m-d'),
            'director' => '',
            'cast' => '',
            'description' => '',
            'image' => '',
            'imdb_id' => '',
            'imdb_rating' => 0,
            'language' => '',
            'country' => '',
            'awards' => '',
            'poster_url' => ''
        ];
        
        if (!isset($movieData['Response']) || $movieData['Response'] !== 'True') {
            return $formatted;
        }
        
        // Map OMDb fields to our database fields
        $formatted['title'] = $movieData['Title'] ?? '';
        $formatted['genre'] = $movieData['Genre'] ?? '';
        
        // Convert runtime to minutes
        if (isset($movieData['Runtime'])) {
            $runtime = $movieData['Runtime'];
            $minutes = 0;
            
            if (preg_match('/(\d+)\s*min/', $runtime, $matches)) {
                $minutes = (int)$matches[1];
            }
            
            $formatted['duration'] = $minutes;
        }
        
        // Format release date
        if (isset($movieData['Released']) && $movieData['Released'] != 'N/A') {
            $releaseDate = date_create_from_format('d M Y', $movieData['Released']);
            if ($releaseDate) {
                $formatted['release_date'] = date_format($releaseDate, 'Y-m-d');
            }
        } else if (isset($movieData['Year']) && $movieData['Year'] != 'N/A') {
            $formatted['release_date'] = $movieData['Year'] . '-01-01';
        }
        
        $formatted['director'] = $movieData['Director'] ?? '';
        $formatted['cast'] = $movieData['Actors'] ?? '';
        $formatted['description'] = $movieData['Plot'] ?? '';
        $formatted['imdb_id'] = $movieData['imdbID'] ?? '';
        
        // Convert IMDb rating to float
        if (isset($movieData['imdbRating']) && $movieData['imdbRating'] != 'N/A') {
            $formatted['imdb_rating'] = (float)$movieData['imdbRating'];
        }
        
        $formatted['language'] = $movieData['Language'] ?? '';
        $formatted['country'] = $movieData['Country'] ?? '';
        $formatted['awards'] = $movieData['Awards'] ?? '';
        $formatted['poster_url'] = $movieData['Poster'] ?? '';
        
        return $formatted;
    }
}
?>