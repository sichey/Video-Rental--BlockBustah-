

document.addEventListener('DOMContentLoaded', () => {
    const loginButton = document.getElementById('loginButton');
    const registerButton = document.getElementById('registerButton');
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    const closes = document.getElementsByClassName('close');

    loginButton.onclick = function() {
        loginModal.style.display = 'block';
    }

    registerButton.onclick = function() {
        registerModal.style.display = 'block';
    }

    Array.from(closes).forEach(function(element) {
        element.onclick = function() {
            loginModal.style.display = 'none';
            registerModal.style.display = 'none';
        }
    });

    window.onclick = function(event) {
        if (event.target == loginModal) {
            loginModal.style.display = 'none';
        }
        if (event.target == registerModal) {
            registerModal.style.display = 'none';
        }
    }
});

function showUpdateForm(movieId) {

    var form = document.getElementById('update-form-' + movieId);

    if (!form) return;

    if (form.style.display === 'block') {
        form.style.display = 'none';
    } else {
        form.style.display = 'block';
    }
}

function confirmDelete() {
    return confirm('Are you sure you want to delete this movie?');
}



// RENTAL MODAL
document.addEventListener('DOMContentLoaded', function() {
    const rentButtons = document.querySelectorAll('.rent-button');
    const rentalModal = document.getElementById('rentalModal');
    const loginModal = document.getElementById('loginModal');

    function clearActiveClasses() {
        document.querySelectorAll('.movie-card.active').forEach(card => {
            card.classList.remove('active');
        });
    }

    rentButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            clearActiveClasses(); 
            const movieCard = this.closest('.movie-card');
            if (!movieCard) {
                console.error("Couldn't find movie card.");
                return;
            }
            movieCard.classList.add('active');
            const movieId = this.dataset.movieId; 

            console.log("Activating movie card for movie ID:", movieId); 

            if (isLoggedIn === 'false') {
                loginModal.style.display = 'block';
            } else {
                const videoUrl = movieCard.dataset.videoUrl;
                document.getElementById('selectedMovieId').value = movieId;
                document.getElementById('rentalMovieTitle').textContent = movieCard.dataset.title;
                document.getElementById('rentalMovieRating').textContent = movieCard.dataset.rating;
                document.getElementById('rentalMoviePrice').textContent = movieCard.dataset.price;
                document.getElementById('rentalDays').value = ''; 
                document.getElementById('rentalTotalCost').textContent = '0.00'; 
                document.getElementById('userBalance').textContent = userBalance; 

                let trailerElement = document.getElementById('rentalMovieTrailer');
                if (videoUrl) {
                    if (!trailerElement) {
                        trailerElement = document.createElement('iframe');
                        trailerElement.id = 'rentalMovieTrailer';
                        trailerElement.src = videoUrl;
                        trailerElement.frameborder = '0';
                        trailerElement.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
                        trailerElement.allowfullscreen = 'true';
                        document.querySelector('#rentalModal .modal-content').prepend(trailerElement);
                    } else {
                        trailerElement.src = videoUrl;
                    }
                }

                const modalImage = document.getElementById('rentalModalImage');
                if (modalImage) {
                    modalImage.src = movieCard.dataset.imageurl;
                }

                rentalModal.style.display = 'block';
            }
        });
    });

    document.querySelectorAll('.close').forEach(closeButton => {
        closeButton.addEventListener('click', () => {
            document.getElementById('rentalModal').style.display = 'none';
            clearActiveClasses(); 
        });
    });

    window.addEventListener('click', function(event) {
        if (event.target === rentalModal) {
            rentalModal.style.display = 'none';
            clearActiveClasses(); 
        }
    });
});

function updateTotalCost() {
    const initialPrice = parseFloat(document.getElementById('rentalMoviePrice').textContent);
    const days = parseInt(document.getElementById('rentalDays').value, 10);
    const additionalCostPerDay = 5; 

    
    const totalCost = initialPrice + ((days - 1) * additionalCostPerDay);
    document.getElementById('rentalTotalCost').textContent = totalCost.toFixed(2); 
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}


function handleCheckout() {
    const movieCard = document.querySelector('.movie-card.active');
    if (!movieCard) {
        alert('No movie selected.');
        return;
    }

    const movieId = movieCard.dataset.movieId;
    if (!movieId) {
        console.error("Movie ID is undefined.");
        alert("Error occurred. Please try again.");
        return;
    }

    const rentalDaysInput = document.getElementById('rentalDays').value;
    const rentalDays = parseInt(rentalDaysInput, 10); 
    if (isNaN(rentalDays) || rentalDays <= 0) {
        alert('Please enter a valid number of days.');
        return;
    }

    //const rentalDays = document.getElementById('rentalDays').value;
    const totalCost = parseFloat(document.getElementById('rentalTotalCost').textContent);
    let currentBalance = parseFloat(document.getElementById('userBalance').textContent);

    if (currentBalance < totalCost) {
        alert('Insufficient balance to complete this transaction.');
        return;
    }

    let newBalance = currentBalance - totalCost; 

    updateBalanceAndProcessRental(movieId, rentalDays, currentBalance, totalCost)
        .then(() => {
            document.getElementById('userBalance').textContent = newBalance.toFixed(2); 
            alert('Checkout successful!');
            closeModal('rentalModal');
        })
        .catch(error => {
            console.error('Checkout or rental process error:', error);
            alert(error.toString());
        });
}

function updateBalanceAndProcessRental(movieId, rentalDays, currentBalance, totalCost) {
    return new Promise((resolve, reject) => {
       
        const updateFormData = new FormData();
        updateFormData.append('newBalance', (currentBalance - totalCost).toFixed(2));
        updateFormData.append('movieId', movieId);
        updateFormData.append('rentalDays', rentalDays);

        
        fetch('update_balance.php', {
            method: 'POST',
            body: updateFormData
        })
        .then(response => response.json())
        .then(updateData => {
            if (!updateData.success) {
                throw new Error(updateData.message || 'Balance update failed');
            }
            
            const rentalFormData = new FormData();
            rentalFormData.append('movieId', movieId);
            rentalFormData.append('rentalDays', rentalDays);

            return fetch('process_rental.php', {
                method: 'POST',
                body: rentalFormData
            });
        })
        .then(response => response.json())
        .then(rentalData => {
            if (!rentalData.success) {
                throw new Error(rentalData.message || 'Rental processing failed');
            }
            
            resolve();
        })
        .catch(error => {
            reject(error);
        });
    });
}

document.getElementById('checkoutButton').addEventListener('click', handleCheckout);




function fetchRentalHistory() {
    fetch('path_to_your_endpoint/rental_history.php', {
        method: 'GET', 
        headers: {
            'Content-Type': 'application/json',
            
        },
        // body: JSON.stringify({ userId: '...' }) // If using 'POST'
    })
    .then(response => response.json())
    .then(data => {
        const historyContainer = document.querySelector('.rental-history-container');
        historyContainer.innerHTML = ''; 
        data.forEach(record => {
            const recordElement = `<div class="rental-record">
                <p>Title: ${record.title}</p>
                <p>Cost: ${record.cost}</p>
                <p>Start Date: ${record.startDate}</p>
                <p>Expiration Date: ${record.expirationDate}</p>
            </div>`;
            historyContainer.innerHTML += recordElement;
        });
    })
    .catch(error => console.error('Error:', error));
}


function updateAvailability(movieId, newStock) {
    const movieCard = document.querySelector(`.movie-card[data-movie-id="${movieId}"]`);
    const rentButton = movieCard.querySelector('.rent-button');
    const outOfStockButton = movieCard.querySelector('.out-of-stock-button');

    if (newStock > 0) {
        if (rentButton) {
            rentButton.disabled = false;
        }
        if (outOfStockButton) {
            outOfStockButton.remove();
        }
        
        if (!rentButton) {
            const newRentButton = document.createElement('a');
            newRentButton.href = "#";
            newRentButton.classList.add('rent-button');
            newRentButton.textContent = 'Rent';
            newRentButton.dataset.movieId = movieId;
            movieCard.append(newRentButton);
        }
    } else {
        if (rentButton) {
            rentButton.remove();
        }
        
        if (!outOfStockButton) {
            const newOutOfStockButton = document.createElement('button');
            newOutOfStockButton.classList.add('out-of-stock-button');
            newOutOfStockButton.disabled = true;
            newOutOfStockButton.textContent = 'Out of Stock';
            movieCard.append(newOutOfStockButton);
        }
    }
}

$(document).ready(function() {
    
    $("#filterToggle").click(function() {
        $("#filterMenu").slideToggle(); 
    });

    
    $(document).click(function(event) { 
        if(!$(event.target).closest('#filterMenu, #filterToggle').length &&
            $('#filterMenu').is(":visible")) {
            $('#filterMenu').slideUp();
        }        
    });
});


$(document).ready(function() {

    /*$(document).on('click', '.search-button', function() {
        // Get the movie title from the button's data attribute
        var movieTitle = $(this).data('movie-title');
        $('#liveSearchQuery').val(movieTitle);

    });*/

    // Live search handler
    $("#liveSearchQuery").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".movie-gallery .movie-card").filter(function() {
            $(this).toggle($(this).attr("data-title").toLowerCase().indexOf(value) > -1);
        });
    });

    // Filter change handler
    $(".filter-form select").change(function() {
        var filters = {
            release_date: $("#releaseDateFilter").val(),
            genre: $("#genreFilter").val(),
            rating: $("#ratingFilter").val(),
            price: $("#priceFilter").val()
        };

        $.ajax({
            url: 'filter.php',
            method: 'GET',
            data: filters,
            success: function(data) {
                $(".movie-gallery").html(data);
            }
        });
    });
});
