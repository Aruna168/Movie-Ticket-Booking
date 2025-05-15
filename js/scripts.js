document.addEventListener("DOMContentLoaded", function () {
    let movieContainer = document.querySelector(".movie-container");

    // Avoid re-adding content if already exists
    if (!movieContainer.hasChildNodes()) {
        let movies = [
            {
                title: "Movie Title 1",
                img: "assets/images/movie1.jpg",
                rating: "⭐⭐⭐⭐",
                year: "2024"
            },
            {
                title: "Movie Title 2",
                img: "assets/images/movie2.jpg",
                rating: "⭐⭐⭐⭐⭐",
                year: "2023"
            }
        ];

        movies.forEach(movie => {
            let movieCard = document.createElement("div");
            movieCard.classList.add("movie-card");

            movieCard.innerHTML = `
                <img src="${movie.img}" alt="${movie.title}">
                <h3>${movie.title}</h3>
                <p>Rating: ${movie.rating}</p>
                <p>Release Year: ${movie.year}</p>
                <button class="book-btn">Book Now</button>
            `;

            movieContainer.appendChild(movieCard);
        });
    }
});
document.addEventListener("DOMContentLoaded", function () {
    fetch("fetch_movies.php")
        .then(response => response.json())
        .then(data => {
            const moviesGrid = document.getElementById("moviesGrid");
            moviesGrid.innerHTML = ""; // Clear existing movies
            
            data.forEach(movie => {
                const movieCard = document.createElement("div");
                movieCard.classList.add("movie-card");
                movieCard.dataset.genre = movie.genre.toLowerCase();

                movieCard.innerHTML = `
                    <img src="${movie.image}" alt="${movie.title}">
                    <div class="movie-info">
                        <h3>${movie.title}</h3>
                        <p>⭐ ${movie.rating}/10</p>
                    </div>
                `;

                moviesGrid.appendChild(movieCard);
            });
        })
        .catch(error => console.error("Error fetching movies:", error));
});

document.addEventListener("DOMContentLoaded", function () {
    const movies = [
        { title: "Avengers: Endgame", genre: "action", image: "../assets/images/movie1.jpg", duration: "3h 2m", rating: "8.4", release: 2019 },
        { title: "The Dark Knight", genre: "action", image: "../assets/images/movie2.jpg", duration: "2h 32m", rating: "9.0", release: 2008 },
        { title: "Inception", genre: "sci-fi", image: "../assets/images/movie3.jpg", duration: "2h 28m", rating: "8.8", release: 2010 },
        { title: "Joker", genre: "thriller", image: "../assets/images/movie4.jpg", duration: "2h 2m", rating: "8.4", release: 2019 },
        { title: "Interstellar", genre: "sci-fi", image: "../assets/images/movie5.jpg", duration: "2h 49m", rating: "8.6", release: 2014 },
        { title: "IT Chapter Two", genre: "horror", image: "../assets/images/movie6.jpg", duration: "2h 49m", rating: "6.5", release: 2019 },
        { title: "The Hangover", genre: "comedy", image: "../assets/images/movie7.jpg", duration: "1h 40m", rating: "7.7", release: 2009 },
    ];

    const movieList = document.getElementById("movieList");
    const searchBar = document.getElementById("searchBar");
    const categoryFilter = document.getElementById("categoryFilter");

    // Function to display movies
    function displayMovies(filteredMovies) {
        movieList.innerHTML = "";
        filteredMovies.forEach(movie => {
            const movieCard = document.createElement("div");
            movieCard.classList.add("movie-card");
            movieCard.innerHTML = `
                <img src="${movie.image}" alt="${movie.title}">
                <h2>${movie.title}</h2>
                <div class="movie-details">
                    <p><strong>Genre:</strong> ${movie.genre.charAt(0).toUpperCase() + movie.genre.slice(1)}</p>
                    <p><strong>Duration:</strong> ${movie.duration}</p>
                    <p><strong>Rating:</strong> ⭐${movie.rating}</p>
                    <p><strong>Release Year:</strong> ${movie.release}</p>
                </div>
                <button class="book-btn">Book Now</button>
            `;
            movieList.appendChild(movieCard);
        });
    }

    // Sort movies by latest release year first
    const sortedMovies = movies.sort((a, b) => b.release - a.release);

    // Initial display of movies (sorted by latest release)
    displayMovies(sortedMovies);

    // Search functionality
    searchBar.addEventListener("input", function () {
        const searchText = searchBar.value.toLowerCase();
        const filteredMovies = sortedMovies.filter(movie => 
            movie.title.toLowerCase().includes(searchText) ||
            movie.genre.toLowerCase().includes(searchText)
        );
        displayMovies(filteredMovies);
    });

    // Category filter functionality
    categoryFilter.addEventListener("change", function () {
        const selectedCategory = categoryFilter.value;
        const filteredMovies = selectedCategory === "all" ? sortedMovies : sortedMovies.filter(movie => movie.genre === selectedCategory);
        displayMovies(filteredMovies);
    });
});

document.addEventListener("DOMContentLoaded", function () {
    console.log("JavaScript Loaded!");  // Check if script is loading

    const movieList = document.getElementById("movieList");
    if (!movieList) {
        console.error("❌ ERROR: #movieList not found in movies.html");
        return;
    }

    const movies = [
        { title: "Avengers: Endgame", genre: "action", image: "../assets/images/movie1.jpg", duration: "3h 2m", rating: "8.4", release: 2019 },
        { title: "The Dark Knight", genre: "action", image: "../assets/images/movie2.jpg", duration: "2h 32m", rating: "9.0", release: 2008 },
        { title: "Inception", genre: "sci-fi", image: "../assets/images/movie3.jpg", duration: "2h 28m", rating: "8.8", release: 2010 },
        { title: "Joker", genre: "thriller", image: "../assets/images/movie4.jpg", duration: "2h 2m", rating: "8.4", release: 2019 },
        { title: "Interstellar", genre: "sci-fi", image: "../assets/images/movie5.jpg", duration: "2h 49m", rating: "8.6", release: 2014 },
        { title: "IT Chapter Two", genre: "horror", image: "../assets/images/movie6.jpg", duration: "2h 49m", rating: "6.5", release: 2019 },
        { title: "The Hangover", genre: "comedy", image: "../assets/images/movie7.jpg", duration: "1h 40m", rating: "7.7", release: 2009 },
    ];

    console.log("✅ Movies Loaded:", movies);  // Check if movies are loaded

    function displayMovies(filteredMovies) {
        console.log("🔄 Updating Movie List...");
        movieList.innerHTML = "";
        filteredMovies.forEach(movie => {
            const movieCard = document.createElement("div");
            movieCard.classList.add("movie-card");
            movieCard.innerHTML = `
                <img src="${movie.image}" alt="${movie.title}">
                <h2>${movie.title}</h2>
                <div class="movie-details">
                    <p><strong>Genre:</strong> ${movie.genre}</p>
                    <p><strong>Duration:</strong> ${movie.duration}</p>
                    <p><strong>Rating:</strong> ⭐${movie.rating}</p>
                    <p><strong>Release Year:</strong> ${movie.release}</p>
                </div>
                <button class="book-btn">Book Now</button>
            `;
            movieList.appendChild(movieCard);
        });
    }

    const sortedMovies = movies.sort((a, b) => b.release - a.release);
    displayMovies(sortedMovies);
});
