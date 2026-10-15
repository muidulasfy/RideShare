document.addEventListener(
    "DOMContentLoaded",
    function () {

        loadMyRides("upcoming");

        setupButtons();

    }
);


// ==========================================
// LOAD MY RIDES
// ==========================================

function loadMyRides(status) {

    const container =
        document.getElementById(
            "ridesContainer"
        );


    container.innerHTML = `
        <div class="loading">

            <div class="loader"></div>

            Loading your rides...

        </div>
    `;


    fetch(
        "my_posted_rides.php?status=" +
        encodeURIComponent(status)
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

            container.innerHTML = `
                <div class="no-rides">

                    <div class="empty-icon">
                        !
                    </div>

                    <h3>
                        Unable to load rides
                    </h3>

                    <p>
                        ${escapeHTML(
                            data.message ||
                            "Something went wrong."
                        )}
                    </p>

                </div>
            `;

            return;
        }


        const rides =
            Array.isArray(data.rides)
                ? data.rides
                : [];


        if (rides.length === 0) {

            container.innerHTML = `
                <div class="no-rides">

                    <div class="empty-icon">
                        🚗
                    </div>

                    <h3>
                        No ${escapeHTML(
                            status
                        )} rides
                    </h3>

                    <p>
                        Your ${escapeHTML(
                            status
                        )} rides will appear here.
                    </p>

                </div>
            `;

            return;
        }


        container.innerHTML = "";


        rides.forEach(
            function (ride, index) {

                renderRide(
                    ride,
                    container,
                    index,
                    status
                );

            }
        );

    })

    .catch(function (error) {

        console.error(error);


        container.innerHTML = `
            <div class="no-rides">

                <div class="empty-icon">
                    !
                </div>

                <h3>
                    Unable to load rides
                </h3>

                <p>
                    Please refresh the page
                    and try again.
                </p>

            </div>
        `;

    });
}


// ==========================================
// RENDER ONE RIDE
// ==========================================

function renderRide(
    ride,
    container,
    index,
    currentStatus
) {

    const card =
        document.createElement(
            "article"
        );


    card.className =
        "ride-card";


    card.style.animationDelay =
        (index * 0.08) + "s";


    // ======================================
    // RIDE DATE
    // ======================================

    const dateText =
        formatDate(
            ride.ride_date
        );


    // ======================================
    // RIDE TIME
    // ======================================

    const timeText =
        formatTime(
            ride.ride_time
        );


    // ======================================
    // VEHICLE
    // ======================================

    const vehicleType =
        String(
            ride.vehicle_type || "Car"
        );


    const vehicleIcon =
        getVehicleIcon(
            vehicleType
        );


    // ======================================
    // SEATS
    // ======================================

    const bookedSeats =
        Number(
            ride.booked_seats || 0
        );


    const availableSeats =
        Number(
            ride.available_seats || 0
        );


    const totalSeats =
        bookedSeats +
        availableSeats;


    // ======================================
    // FEMALE ONLY
    // ======================================

    const femaleOnly =
        Number(
            ride.female_only
        ) === 1;


    // ======================================
    // RECURRING
    // ======================================

    const recurring =
        Number(
            ride.recurring
        ) === 1;


    // ======================================
    // RIDE STARTED?
    // ======================================

    const rideDateTime =
        new Date(
            String(
                ride.ride_date
            ) +
            "T" +
            String(
                ride.ride_time
            )
        );


    const now =
        new Date();


    const rideStarted =
        rideDateTime <= now;


    // ======================================
    // STATUS
    // ======================================

    let statusText =
        "Upcoming";


    let statusClass =
        "upcoming";


    if (
        ride.status ===
        "completed"
    ) {

        statusText =
            "Completed";

        statusClass =
            "completed";

    }

    else if (
        ride.status ===
        "cancelled"
    ) {

        statusText =
            "Cancelled";

        statusClass =
            "cancelled";
    }


    // ======================================
    // ACTIONS
    // ======================================

    let actionsHTML =
        "";


    // ======================================
    // UPCOMING RIDE
    // ======================================

    if (
        currentStatus ===
        "upcoming"
    ) {

        /*
            Before scheduled time:
            Edit + Cancel

            After scheduled time:
            Complete Ride
        */

        if (!rideStarted) {

            actionsHTML = `

                <a
                    href="edit_ride.html?id=${encodeURIComponent(
                        ride.id
                    )}"
                    class="edit-btn"
                >
                    Edit Ride
                </a>


                <button
                    type="button"
                    class="cancel-btn"
                    data-ride-id="${escapeHTML(
                        String(ride.id)
                    )}"
                >
                    Cancel Ride
                </button>

            `;

        }

        else {

            actionsHTML = `

                <button
                    type="button"
                    class="complete-btn"
                    data-ride-id="${escapeHTML(
                        String(ride.id)
                    )}"
                >
                    ✓ Complete Ride
                </button>

            `;
        }

    }


    // ======================================
    // COMPLETED / CANCELLED
    // ======================================

    else {

        actionsHTML = `

            <span class="no-action">

                ${
                    currentStatus ===
                    "completed"

                        ? "Ride completed"

                        : "Ride cancelled"

                }

            </span>

        `;
    }


    // ======================================
    // RIDE CARD
    // ======================================

    card.innerHTML = `

        <!-- ==============================
             TOP
        =============================== -->

        <div class="ride-top">


            <!-- ROUTE -->

            <div class="route-section">

                <div class="location-label">

                    <span class="location-dot"></span>

                    ${escapeHTML(
                        ride.from_location ||
                        ""
                    )}

                </div>


                <div class="route-line"></div>


                <div class="location-label">

                    <span class="destination-dot"></span>

                    ${escapeHTML(
                        ride.to_location ||
                        ""
                    )}

                </div>

            </div>


            <!-- STATUS -->

            <div
                class="ride-status ${statusClass}"
            >

                <span></span>

                ${statusText}

            </div>

        </div>


        <!-- ==============================
             DETAILS
        =============================== -->

        <div class="ride-middle">


            <!-- DATE -->

            <div class="ride-detail">

                <div class="detail-icon">
                    📅
                </div>

                <div>

                    <small>
                        Date
                    </small>

                    <strong>
                        ${escapeHTML(
                            dateText
                        )}
                    </strong>

                </div>

            </div>


            <!-- TIME -->

            <div class="ride-detail">

                <div class="detail-icon">
                    🕐
                </div>

                <div>

                    <small>
                        Time
                    </small>

                    <strong>
                        ${escapeHTML(
                            timeText
                        )}
                    </strong>

                </div>

            </div>


            <!-- SEATS -->

            <div class="ride-detail">

                <div class="detail-icon">
                    👥
                </div>

                <div>

                    <small>
                        Seats
                    </small>

                    <strong>
                        ${bookedSeats}/${totalSeats}
                        booked
                    </strong>

                    <em>
                        ${availableSeats}
                        available
                    </em>

                </div>

            </div>


            <!-- PRICE -->

            <div class="ride-detail">

                <div class="detail-icon price-icon">
                    ৳
                </div>

                <div>

                    <small>
                        Price
                    </small>

                    <strong>
                        ৳${escapeHTML(
                            String(
                                ride.price_per_seat ??
                                "0"
                            )
                        )}
                    </strong>

                    <em>
                        per seat
                    </em>

                </div>

            </div>

        </div>


        <!-- ==============================
             BOTTOM
        =============================== -->

        <div class="ride-bottom">


            <!-- TAGS -->

            <div class="ride-tags">


                ${
                    femaleOnly

                        ? `
                            <span class="tag female-tag">
                                ♀ Female Only
                            </span>
                        `

                        : `
                            <span class="tag normal-tag">
                                👥 Open Ride
                            </span>
                        `
                }


                <span class="tag seats-tag">

                    ${availableSeats}
                    seat${
                        availableSeats === 1
                            ? ""
                            : "s"
                    }
                    available

                </span>


                ${
                    recurring

                        ? `
                            <span class="tag normal-tag">
                                🔄 Recurring
                            </span>
                        `

                        : ""
                }

            </div>


            <!-- VEHICLE -->

            <div class="vehicle-section">

                <div class="vehicle-icon">

                    ${vehicleIcon}

                </div>

                <span>
                    ${escapeHTML(
                        vehicleType
                    )}
                </span>

            </div>


            <!-- ACTIONS -->

            <div class="ride-actions">

                ${actionsHTML}

            </div>


        </div>

    `;


    // ======================================
    // CANCEL BUTTON
    // ======================================

    const cancelButton =
        card.querySelector(
            ".cancel-btn"
        );


    if (cancelButton) {

        cancelButton.addEventListener(
            "click",
            function () {

                const rideId =
                    this.getAttribute(
                        "data-ride-id"
                    );


                cancelRide(
                    rideId
                );

            }
        );
    }


    // ======================================
    // COMPLETE BUTTON
    // ======================================

    const completeButton =
        card.querySelector(
            ".complete-btn"
        );


    if (completeButton) {

        completeButton.addEventListener(
            "click",
            function () {

                const rideId =
                    this.getAttribute(
                        "data-ride-id"
                    );


                completeRide(
                    rideId,
                    this
                );

            }
        );
    }


    // ======================================
    // ADD CARD
    // ======================================

    container.appendChild(
        card
    );
}


// ==========================================
// SETUP TABS
// ==========================================

function setupButtons() {

    const upcomingBtn =
        document.getElementById(
            "upcomingBtn"
        );


    const completedBtn =
        document.getElementById(
            "completedBtn"
        );


    const cancelledBtn =
        document.getElementById(
            "cancelledBtn"
        );


    upcomingBtn.addEventListener(
        "click",
        function () {

            setActiveButton(
                this
            );

            loadMyRides(
                "upcoming"
            );

        }
    );


    completedBtn.addEventListener(
        "click",
        function () {

            setActiveButton(
                this
            );

            loadMyRides(
                "completed"
            );

        }
    );


    cancelledBtn.addEventListener(
        "click",
        function () {

            setActiveButton(
                this
            );

            loadMyRides(
                "cancelled"
            );

        }
    );
}


// ==========================================
// ACTIVE TAB
// ==========================================

function setActiveButton(
    button
) {

    document
        .querySelectorAll(
            "#upcomingBtn, #completedBtn, #cancelledBtn"
        )
        .forEach(
            function (btn) {

                btn.classList.remove(
                    "active"
                );

            }
        );


    button.classList.add(
        "active"
    );
}


// ==========================================
// COMPLETE RIDE
// ==========================================

function completeRide(
    rideId,
    button
) {

    const confirmed =
        window.confirm(
            "Are you sure you want to mark this ride as completed?"
        );


    if (!confirmed) {

        return;
    }


    button.disabled =
        true;


    button.textContent =
        "Completing...";


    const formData =
        new FormData();


    formData.append(
        "ride_id",
        rideId
    );


    fetch(
        "complete_ride.php",
        {
            method: "POST",
            body: formData
        }
    )

    .then(function (response) {

        return response.json();

    })

    .then(function (data) {

        if (!data.success) {

            showToast(
                data.message ||
                "Ride could not be completed.",
                "error"
            );


            button.disabled =
                false;


            button.textContent =
                "✓ Complete Ride";


            return;
        }


        showToast(
            data.message ||
            "Ride completed successfully.",
            "success"
        );


        setTimeout(
            function () {

                loadMyRides(
                    "upcoming"
                );

            },
            500
        );

    })

    .catch(function (error) {

        console.error(
            error
        );


        showToast(
            "Something went wrong while completing the ride.",
            "error"
        );


        button.disabled =
            false;


        button.textContent =
            "✓ Complete Ride";

    });
}


// ==========================================
// CANCEL RIDE
// ==========================================

function cancelRide(
    rideId
) {

    const confirmed =
        window.confirm(
            "Cancel this ride?\n\nAll active passenger bookings for this ride will also be cancelled."
        );


    if (!confirmed) {

        return;
    }


    const formData =
        new FormData();


    formData.append(
        "ride_id",
        rideId
    );


    fetch(
        "cancel_ride.php",
        {
            method: "POST",
            body: formData
        }
    )

    .then(function (response) {

        return response.json();

    })

    .then(function (data) {

        if (!data.success) {

            showToast(
                data.message ||
                "Ride could not be cancelled.",
                "error"
            );

            return;
        }


        showToast(
            data.message ||
            "Ride cancelled successfully.",
            "success"
        );


        setTimeout(
            function () {

                loadMyRides(
                    "upcoming"
                );

            },
            500
        );

    })

    .catch(function (error) {

        console.error(
            error
        );


        showToast(
            "Something went wrong while cancelling the ride.",
            "error"
        );

    });
}


// ==========================================
// TOAST
// ==========================================

function showToast(
    message,
    type
) {

    const toast =
        document.getElementById(
            "toast"
        );


    toast.textContent =
        message;


    toast.className =
        "toast show " +
        (type || "");


    setTimeout(
        function () {

            toast.className =
                "toast";

        },
        3200
    );
}


// ==========================================
// FORMAT DATE
// ==========================================

function formatDate(
    date
) {

    if (!date) {

        return "";
    }


    const parts =
        String(date).split("-");


    if (
        parts.length !== 3
    ) {

        return date;
    }


    const year =
        parts[0];


    const month =
        Number(parts[1]);


    const day =
        Number(parts[2]);


    const months = [

        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",

        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec"

    ];


    return (
        months[month - 1] +
        " " +
        day +
        ", " +
        year
    );
}


// ==========================================
// FORMAT TIME
// ==========================================

function formatTime(
    time
) {

    if (!time) {

        return "";
    }


    const parts =
        String(time).split(":");


    if (
        parts.length < 2
    ) {

        return time;
    }


    let hour =
        parseInt(
            parts[0],
            10
        );


    const minute =
        parts[1];


    const suffix =
        hour >= 12
            ? "PM"
            : "AM";


    hour =
        hour % 12;


    if (hour === 0) {

        hour = 12;
    }


    return (
        hour +
        ":" +
        minute +
        " " +
        suffix
    );
}


// ==========================================
// VEHICLE ICON
// ==========================================

function getVehicleIcon(
    vehicle
) {

    const value =
        String(
            vehicle
        ).toLowerCase();


    if (
        value.includes("bike") ||
        value.includes("motorcycle")
    ) {

        return "🏍️";
    }


    if (
        value.includes("cng") ||
        value.includes("auto")
    ) {

        return "🛺";
    }


    if (
        value.includes("hiace") ||
        value.includes("van")
    ) {

        return "🚐";
    }


    if (
        value.includes("bus")
    ) {

        return "🚌";
    }


    return "🚗";
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