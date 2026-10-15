document.addEventListener(
    "DOMContentLoaded",
    function () {

        const bookingList =
            document.querySelector(
                ".booking-list"
            );


        const tabs =
            document.querySelectorAll(
                ".tab"
            );


        let bookings = [];

        let currentTab =
            "upcoming";


        // ======================================
        // LOAD BOOKINGS
        // ======================================

        function loadBookings() {

            fetch("My Rides.php")

                .then(function (response) {

                    if (!response.ok) {

                        throw new Error(
                            "Could not load bookings."
                        );
                    }

                    return response.json();

                })

                .then(function (data) {

                    if (!data.success) {

                        bookingList.innerHTML =
                            `
                            <p>
                                ${escapeHTML(
                                    data.message ||
                                    "Unable to load bookings."
                                )}
                            </p>
                            `;

                        return;
                    }


                    bookings =
                        Array.isArray(
                            data.bookings
                        )
                            ? data.bookings
                            : [];


                    renderBookings();

                })

                .catch(function (error) {

                    console.error(error);


                    bookingList.innerHTML =
                        `
                        <p>
                            Unable to load
                            your bookings.
                        </p>
                        `;

                });
        }


        // ======================================
        // IS RIDE COMPLETED?
        // ======================================

        function isRidePast(booking) {

            return (
                booking.ride_status ===
                    "completed"

                ||

                booking.ride_status ===
                    "cancelled"

                ||

                booking.booking_status ===
                    "cancelled"

                ||

                booking.booking_status ===
                    "completed"
            );
        }


        // ======================================
        // RENDER BOOKINGS
        // ======================================

        function renderBookings() {

            const filteredBookings =
                bookings.filter(
                    function (booking) {

                        const past =
                            isRidePast(
                                booking
                            );


                        if (
                            currentTab ===
                            "past"
                        ) {

                            return past;
                        }


                        return !past;

                    }
                );


            // ==================================
            // NO BOOKINGS
            // ==================================

            if (
                filteredBookings.length === 0
            ) {

                if (
                    currentTab ===
                    "upcoming"
                ) {

                    bookingList.innerHTML =
                        `
                        <div
                            class="empty-bookings"
                        >
                            <p>
                                No upcoming
                                bookings.
                            </p>
                        </div>
                        `;

                }

                else {

                    bookingList.innerHTML =
                        `
                        <div
                            class="empty-bookings"
                        >
                            <p>
                                No past
                                bookings.
                            </p>
                        </div>
                        `;
                }

                return;
            }


            bookingList.innerHTML =
                "";


            // ==================================
            // EACH BOOKING
            // ==================================

            filteredBookings.forEach(
                function (booking) {

                    const card =
                        document.createElement(
                            "div"
                        );


                    card.className =
                        "booking-card";


                    // ==================================
                    // DATE
                    // ==================================

                    const date =
                        new Date(
                            booking.ride_date +
                            "T00:00:00"
                        );


                    const dateText =
                        date.toLocaleDateString(
                            "en-GB",
                            {
                                day: "2-digit",
                                month: "short"
                            }
                        );


                    // ==================================
                    // TIME
                    // ==================================

                    let timeText =
                        booking.ride_time ||
                        "";


                    if (timeText) {

                        timeText =
                            timeText.substring(
                                0,
                                5
                            );
                    }


                    // ==================================
                    // STATUS
                    // ==================================

                    let status =
                        booking.ride_status ||
                        booking.booking_status ||
                        "";


                    if (
                        booking.ride_status ===
                        "completed"
                    ) {

                        status =
                            "Completed";

                    }

                    else if (
                        booking.ride_status ===
                        "cancelled"
                    ) {

                        status =
                            "Cancelled";

                    }

                    else {

                        status =
                            booking.booking_status ||
                            "Confirmed";

                    }


                    status =
                        status
                            .charAt(0)
                            .toUpperCase() +
                        status
                            .slice(1);


                    // ==================================
                    // SEATS
                    // ==================================

                    const seatsBooked =
                        Number(
                            booking.seats_booked
                        );


                    const seatText =
                        seatsBooked === 1

                            ? "Seat 1"

                            : "Seats " +
                              seatsBooked;


                    // ==================================
                    // COMPLETED
                    // ==================================

                    const completed =
                        booking.ride_status ===
                            "completed";


                    // ==================================
                    // RATING
                    // ==================================

                    let ratingHTML = "";


                    if (
                        completed &&
                        booking.rating_id
                    ) {

                        ratingHTML = `
                            <span
                                class="rated-label"
                            >
                                ✓ Rated
                            </span>
                        `;

                    }

                    else if (
                        completed
                    ) {

                        ratingHTML = `
                            <button
                                type="button"
                                class="rate-driver-btn"
                                data-booking-id="${Number(
                                    booking.booking_id
                                )}"
                                data-driver-id="${Number(
                                    booking.driver_id
                                )}"
                                data-driver-name="${escapeHTML(
                                    booking.driver_name
                                )}"
                            >
                                ⭐ Rate Driver
                            </button>
                        `;
                    }


                    // ==================================
                    // MESSAGE
                    // ==================================

                    const messageHTML =
                        !completed
                            ? `
                                <a
                                    href="chat.html?user_id=${Number(
                                        booking.driver_id
                                    )}"
                                    class="message-btn"
                                >
                                    Message Driver
                                </a>
                            `
                            : "";


                    // ==================================
                    // CANCEL
                    // ==================================

                    const cancelHTML =
                        !completed &&
                        booking.ride_status !==
                            "cancelled"

                            ? `
                                <button
                                    class="delete-btn"
                                    data-booking-id="${Number(
                                        booking.booking_id
                                    )}"
                                    type="button"
                                >
                                    🗑
                                </button>
                            `

                            : "";


                    // ==================================
                    // CARD HTML
                    // ==================================

                    card.innerHTML = `

                        <div class="booking-info">

                            <h3>

                                ${escapeHTML(
                                    status
                                )}

                                •

                                ${escapeHTML(
                                    booking.driver_name
                                )}

                            </h3>


                            <p>

                                ${escapeHTML(
                                    booking.from_location
                                )}

                                →

                                ${escapeHTML(
                                    booking.to_location
                                )}

                            </p>


                            <p>

                                ${escapeHTML(
                                    dateText
                                )}

                                •

                                ${escapeHTML(
                                    timeText
                                )}

                                •

                                ${escapeHTML(
                                    seatText
                                )}

                            </p>


                            ${
                                completed
                                    ? `
                                        <p>
                                            ✓ Ride completed
                                        </p>
                                      `
                                    : ""
                            }

                        </div>


                        <div class="booking-actions">

                            ${messageHTML}

                            ${ratingHTML}

                            ${cancelHTML}

                        </div>

                    `;


                    // ==================================
                    // CANCEL EVENT
                    // ==================================

                    const deleteButton =
                        card.querySelector(
                            ".delete-btn"
                        );


                    if (deleteButton) {

                        deleteButton.addEventListener(
                            "click",
                            function () {

                                const bookingId =
                                    this.getAttribute(
                                        "data-booking-id"
                                    );


                                const confirmDelete =
                                    confirm(
                                        "Are you sure you want to cancel this booking?"
                                    );


                                if (
                                    !confirmDelete
                                ) {

                                    return;
                                }


                                const formData =
                                    new FormData();


                                formData.append(
                                    "booking_id",
                                    bookingId
                                );


                                this.disabled =
                                    true;


                                fetch(
                                    "cancel_booking.php",
                                    {
                                        method:
                                            "POST",
                                        body:
                                            formData
                                    }
                                )

                                .then(
                                    function (
                                        response
                                    ) {

                                        return response.json();

                                    }
                                )

                                .then(
                                    function (
                                        data
                                    ) {

                                        if (
                                            data.success
                                        ) {

                                            alert(
                                                data.message
                                            );

                                            loadBookings();

                                        }

                                        else {

                                            alert(
                                                data.message
                                            );

                                            deleteButton.disabled =
                                                false;
                                        }

                                    }
                                )

                                .catch(
                                    function (
                                        error
                                    ) {

                                        console.error(
                                            error
                                        );

                                        alert(
                                            "Something went wrong while cancelling the booking."
                                        );

                                        deleteButton.disabled =
                                            false;

                                    }
                                );

                            }
                        );
                    }


                    // ==================================
                    // RATE BUTTON
                    // ==================================

                    const rateButton =
                        card.querySelector(
                            ".rate-driver-btn"
                        );


                    if (rateButton) {

                        rateButton.addEventListener(
                            "click",
                            function () {

                                openRatingModal(
                                    this
                                );

                            }
                        );
                    }


                    bookingList.appendChild(
                        card
                    );

                }
            );

        }


        // ======================================
        // TABS
        // ======================================

        tabs.forEach(
            function (tab, index) {

                tab.addEventListener(
                    "click",
                    function () {

                        tabs.forEach(
                            function (item) {

                                item.classList.remove(
                                    "active"
                                );

                            }
                        );


                        tab.classList.add(
                            "active"
                        );


                        if (index === 0) {

                            currentTab =
                                "upcoming";

                        }

                        else {

                            currentTab =
                                "past";
                        }


                        renderBookings();

                    }
                );

            }
        );


        // ======================================
        // RATING MODAL
        // ======================================

        function openRatingModal(
            button
        ) {

            const bookingId =
                button.dataset.bookingId;


            const driverName =
                button.dataset.driverName;


            const modal =
                document.getElementById(
                    "ratingModal"
                );


            if (!modal) {

                alert(
                    "Rating modal is missing."
                );

                return;
            }


            const bookingInput =
                document.getElementById(
                    "ratingBookingId"
                );


            const driverText =
                document.getElementById(
                    "ratingDriverName"
                );


            const ratingInput =
                document.getElementById(
                    "selectedRating"
                );


            const reviewInput =
                document.getElementById(
                    "ratingReview"
                );


            bookingInput.value =
                bookingId;


            driverText.textContent =
                "Rate " + driverName;


            ratingInput.value =
                "";


            reviewInput.value =
                "";


            document
                .querySelectorAll(
                    ".rating-star"
                )
                .forEach(
                    function (star) {

                        star.classList.remove(
                            "selected"
                        );

                    }
                );


            modal.classList.add(
                "show"
            );
        }


        // ======================================
        // STAR CLICK
        // ======================================

        document
            .querySelectorAll(
                ".rating-star"
            )
            .forEach(
                function (star) {

                    star.addEventListener(
                        "click",
                        function () {

                            const rating =
                                Number(
                                    this.dataset.rating
                                );


                            document
                                .getElementById(
                                    "selectedRating"
                                )
                                .value =
                                rating;


                            document
                                .querySelectorAll(
                                    ".rating-star"
                                )
                                .forEach(
                                    function (
                                        item
                                    ) {

                                        item.classList.toggle(
                                            "selected",
                                            Number(
                                                item.dataset.rating
                                            ) <= rating
                                        );

                                    }
                                );

                        }
                    );

                }
            );


        // ======================================
        // CLOSE MODAL
        // ======================================

        const closeRating =
            document.getElementById(
                "closeRating"
            );


        if (closeRating) {

            closeRating.addEventListener(
                "click",
                function () {

                    closeRatingModal();

                }
            );
        }


        const cancelRating =
            document.getElementById(
                "cancelRating"
            );


        if (cancelRating) {

            cancelRating.addEventListener(
                "click",
                function () {

                    closeRatingModal();

                }
            );
        }


        function closeRatingModal() {

            const modal =
                document.getElementById(
                    "ratingModal"
                );


            if (modal) {

                modal.classList.remove(
                    "show"
                );
            }
        }


        // ======================================
        // SUBMIT RATING
        // ======================================

        const submitRating =
            document.getElementById(
                "submitRating"
            );


        if (submitRating) {

            submitRating.addEventListener(
                "click",
                function () {

                    const bookingId =
                        document.getElementById(
                            "ratingBookingId"
                        ).value;


                    const rating =
                        document.getElementById(
                            "selectedRating"
                        ).value;


                    const review =
                        document.getElementById(
                            "ratingReview"
                        ).value.trim();


                    if (!rating) {

                        alert(
                            "Please select a rating."
                        );

                        return;
                    }


                    const formData =
                        new FormData();


                    formData.append(
                        "booking_id",
                        bookingId
                    );


                    formData.append(
                        "rating",
                        rating
                    );


                    formData.append(
                        "review",
                        review
                    );


                    submitRating.disabled =
                        true;


                    submitRating.textContent =
                        "Submitting...";


                    fetch(
                        "rate_driver.php",
                        {
                            method:
                                "POST",
                            body:
                                formData
                        }
                    )

                    .then(
                        function (
                            response
                        ) {

                            return response.json();

                        }
                    )

                    .then(
                        function (
                            data
                        ) {

                            if (
                                data.success
                            ) {

                                alert(
                                    data.message ||
                                    "Rating submitted successfully."
                                );


                                closeRatingModal();

                                loadBookings();

                            }

                            else {

                                alert(
                                    data.message ||
                                    "Rating failed."
                                );

                            }

                        }
                    )

                    .catch(
                        function (
                            error
                        ) {

                            console.error(
                                error
                            );

                            alert(
                                "Something went wrong while submitting rating."
                            );

                        }
                    )

                    .finally(
                        function () {

                            submitRating.disabled =
                                false;

                            submitRating.textContent =
                                "Submit Rating";

                        }
                    );

                }
            );
        }


        // ======================================
        // ESCAPE HTML
        // ======================================

        function escapeHTML(
            value
        ) {

            const div =
                document.createElement(
                    "div"
                );


            div.textContent =
                value === null ||
                value === undefined

                    ? ""

                    : String(value);


            return div.innerHTML;

        }


        // ======================================
        // START
        // ======================================

        loadBookings();

    }
);