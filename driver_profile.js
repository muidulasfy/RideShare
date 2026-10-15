document.addEventListener(
    "DOMContentLoaded",
    function () {

        loadDriverProfile();

    }
);


// ==========================================
// LOAD DRIVER PROFILE
// ==========================================

function loadDriverProfile() {

    const params =
        new URLSearchParams(
            window.location.search
        );


    const driverId =
        params.get("id");


    if (!driverId) {

        showError(
            "Driver profile not found."
        );

        return;
    }


    fetch(
        "driver_profile.php?id=" +
        encodeURIComponent(
            driverId
        )
    )

    .then(function (response) {

        if (!response.ok) {

            throw new Error(
                "Server error."
            );

        }

        return response.json();

    })

    .then(function (data) {

        if (!data.success) {

            showError(
                data.message ||
                "Unable to load driver."
            );

            return;
        }


        renderDriver(
            data.driver
        );


        renderReviews(
            data.reviews || []
        );

    })

    .catch(function (error) {

        console.error(error);


        showError(
            "Unable to load driver profile."
        );

    });
}


// ==========================================
// RENDER DRIVER
// ==========================================

function renderDriver(
    driver
) {

    const name =
        driver.name ||
        "Driver";


    document.getElementById(
        "driverName"
    ).textContent =
        name;


    document.getElementById(
        "driverInitial"
    ).textContent =
        name
            .charAt(0)
            .toUpperCase();


    // ======================================
    // COMPLETED RIDES
    // ======================================

    document.getElementById(
        "completedRides"
    ).textContent =
        Number(
            driver.completed_rides || 0
        );


    // ======================================
    // RATING
    // ======================================

    if (
        driver.average_rating !== null &&
        driver.average_rating !== undefined
    ) {

        document.getElementById(
            "averageRating"
        ).textContent =
            Number(
                driver.average_rating
            ).toFixed(1);


        document.getElementById(
            "ratingLine"
        ).textContent =

            "★ " +

            Number(
                driver.average_rating
            ).toFixed(1) +

            " average rating";


    }

    else {

        document.getElementById(
            "averageRating"
        ).textContent =
            "—";


        document.getElementById(
            "ratingLine"
        ).textContent =
            "★ No ratings yet";

    }


    // ======================================
    // RATING COUNT
    // ======================================

    document.getElementById(
        "ratingCount"
    ).textContent =
        Number(
            driver.rating_count || 0
        );


    // ======================================
    // VEHICLE
    // ======================================

    document.getElementById(
        "vehicleType"
    ).textContent =
        driver.vehicle_type ||
        "Not available";


    document.getElementById(
        "vehicleModel"
    ).textContent =
        driver.vehicle_model ||
        "Not available";


    document.getElementById(
        "vehicleNumber"
    ).textContent =
        driver.vehicle_number ||
        "Not available";


    // ======================================
    // GENDER
    // ======================================

    const gender =
        driver.gender || "";


    document.getElementById(
        "driverGender"
    ).textContent =
        gender
            ? capitalize(gender)
            : "Not available";
}


// ==========================================
// RENDER REVIEWS
// ==========================================

function renderReviews(
    reviews
) {

    const container =
        document.getElementById(
            "reviewsContainer"
        );


    if (
        !reviews ||
        reviews.length === 0
    ) {

        container.innerHTML = `

            <div class="no-reviews">

                <div class="no-review-icon">
                    ★
                </div>

                <h3>
                    No reviews yet
                </h3>

                <p>
                    This driver has not received
                    any reviews yet.
                </p>

            </div>

        `;

        return;
    }


    container.innerHTML =
        "";


    reviews.forEach(
        function (review) {

            const card =
                document.createElement(
                    "div"
                );


            card.className =
                "review-card";


            const rating =
                Number(
                    review.rating || 0
                );


            const stars =
                "★".repeat(
                    rating
                ) +
                "☆".repeat(
                    5 - rating
                );


            const reviewText =
                review.review &&
                review.review.trim()
                    ? review.review
                    : "No written review.";


            const date =
                formatDate(
                    review.created_at
                );


            card.innerHTML = `

                <div class="review-top">

                    <div>

                        <strong>
                            ${escapeHTML(
                                review.reviewer_name ||
                                "Passenger"
                            )}
                        </strong>

                        <div class="stars">
                            ${stars}
                        </div>

                    </div>


                    <span class="review-date">
                        ${escapeHTML(
                            date
                        )}
                    </span>

                </div>


                <p class="review-text">

                    ${escapeHTML(
                        reviewText
                    )}

                </p>

            `;


            container.appendChild(
                card
            );

        }
    );
}


// ==========================================
// ERROR
// ==========================================

function showError(
    message
) {

    document.getElementById(
        "driverName"
    ).textContent =
        "Driver Profile";


    document.getElementById(
        "reviewsContainer"
    ).innerHTML = `

        <div class="no-reviews">

            <h3>
                ${escapeHTML(
                    message
                )}
            </h3>

        </div>

    `;
}


// ==========================================
// DATE
// ==========================================

function formatDate(
    value
) {

    if (!value) {

        return "";
    }


    const date =
        new Date(
            value
        );


    if (
        Number.isNaN(
            date.getTime()
        )
    ) {

        return String(
            value
        );
    }


    return date.toLocaleDateString(
        "en-GB",
        {
            day: "2-digit",
            month: "short",
            year: "numeric"
        }
    );
}


// ==========================================
// CAPITALIZE
// ==========================================

function capitalize(
    value
) {

    return String(
        value
    )
        .charAt(0)
        .toUpperCase() +

        String(
            value
        ).slice(1);
}


// ==========================================
// ESCAPE HTML
// ==========================================

function escapeHTML(
    value
) {

    return String(
        value === null ||
        value === undefined
            ? ""
            : value
    )

    .replace(
        /&/g,
        "&amp;"
    )

    .replace(
        /</g,
        "&lt;"
    )

    .replace(
        />/g,
        "&gt;"
    )

    .replace(
        /"/g,
        "&quot;"
    )

    .replace(
        /'/g,
        "&#039;"
    );
}