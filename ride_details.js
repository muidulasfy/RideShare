/* =========================================
   RIDENEST - RIDE DETAILS
========================================= */

var currentRideId = null;


// =========================================
// 1. GET RIDE ID
// =========================================

var urlParams =
    new URLSearchParams(
        window.location.search
    );

currentRideId =
    urlParams.get("id");


// =========================================
// 2. LOAD RIDE
// =========================================

document.addEventListener(
    "DOMContentLoaded",
    function () {

        if (!currentRideId) {

            alert(
                "Ride ID not found."
            );

            return;
        }


        fetch(
            "ride_details.php?id=" +
            encodeURIComponent(
                currentRideId
            )
        )

        .then(
            function (response) {

                return response.json();

            }
        )

        .then(
            function (data) {

                if (!data.success) {

                    alert(
                        data.message
                    );

                    return;
                }


                var ride =
                    data.ride;



                // =====================================
                // ROUTE
                // =====================================

                var routeBox =
                    document.querySelector(
                        ".items.blue"
                    );


                if (routeBox) {

                    var routeParagraphs =
                        routeBox.querySelectorAll(
                            "p"
                        );


                    if (
                        routeParagraphs[0]
                    ) {

                        routeParagraphs[0]
                            .textContent =
                            "Pickup: " +
                            (
                                ride.from_location ||
                                "Not specified"
                            );

                    }


                    if (
                        routeParagraphs[1]
                    ) {

                        routeParagraphs[1]
                            .textContent =
                            "Destination: " +
                            (
                                ride.to_location ||
                                "Not specified"
                            );

                    }

                }



                // =====================================
                // ITEMS
                // =====================================

                var items =
                    document.querySelectorAll(
                        ".items"
                    );



                // =====================================
                // PICKUP CHECKPOINT
                // =====================================

                if (items[1]) {

                    var pickupParagraph =
                        items[1]
                            .querySelector("p");


                    if (pickupParagraph) {

                        pickupParagraph
                            .textContent =
                            ride.pickup_checkpoint ||
                            "Not specified";

                    }

                }



                // =====================================
                // DROP CHECKPOINT
                // =====================================

                if (items[2]) {

                    var dropParagraph =
                        items[2]
                            .querySelector("p");


                    if (dropParagraph) {

                        dropParagraph
                            .textContent =
                            ride.drop_checkpoint ||
                            "Not specified";

                    }

                }



                // =====================================
                // SCHEDULE
                // =====================================

                if (items[3]) {

                    var date =
                        ride.ride_date ||
                        "Not specified";


                    var time =
                        ride.ride_time ||
                        "Not specified";


                    var scheduleParagraph =
                        items[3]
                            .querySelector("p");


                    if (scheduleParagraph) {

                        scheduleParagraph
                            .textContent =
                            date +
                            " • " +
                            time;

                    }


                    var scheduleParagraphs =
                        items[3]
                            .querySelectorAll(
                                "p"
                            );


                    var seatParagraph =
                        scheduleParagraphs[1];


                    if (seatParagraph) {

                        seatParagraph
                            .textContent =
                            ride.available_seats +
                            " seats available";

                    }

                }



                // =====================================
                // DRIVER NAME
                // =====================================

                var driverName =
                    document.querySelector(
                        ".driver-info h2"
                    );


                if (driverName) {

                    driverName.textContent =
                        ride.driver_name ||
                        "Unknown driver";

                }



                // =====================================
// VEHICLE
// =====================================

var vehicleInfo =
    document.getElementById(
        "vehicleInfo"
    );


if (vehicleInfo) {

    var vehicleText =
        ride.vehicle_type ||
        "Vehicle information not available";


    if (
        Number(
            ride.female_only
        ) === 1
    ) {

        vehicleText +=
            " • Female-only ride";

    }


    vehicleInfo.textContent =
        vehicleText;

}


                // =====================================
                // DRIVER RATING
                // =====================================

                var driverRating =
                    document.getElementById(
                        "driverRating"
                    );


                if (driverRating) {

                    var rating =
                        Number(
                            ride.driver_rating ||
                            0
                        );


                    var ratingCount =
                        Number(
                            ride.rating_count ||
                            0
                        );


                    if (
                        ratingCount > 0 &&
                        rating > 0
                    ) {

                        driverRating.textContent =
                            "★ " +
                            rating.toFixed(1) +
                            " average rating (" +
                            ratingCount +
                            " review" +
                            (
                                ratingCount === 1
                                    ? ""
                                    : "s"
                            ) +
                            ")";

                    }

                    else {

                        driverRating.textContent =
                            "★ No ratings yet";

                    }

                }



                // =====================================
                // VIEW DRIVER PROFILE
                // =====================================

                var viewDriverProfile =
                    document.getElementById(
                        "viewDriverProfile"
                    );


                if (viewDriverProfile) {

                    if (
                        ride.driver_id
                    ) {

                        viewDriverProfile.href =
                            "driver_profile.html?id=" +
                            encodeURIComponent(
                                ride.driver_id
                            );


                        viewDriverProfile.style.display =
                            "inline-block";

                    }

                    else {

                        viewDriverProfile.style.display =
                            "none";

                    }

                }

            }
        )


        .catch(
            function (error) {

                console.error(
                    "Ride details error:",
                    error
                );


                alert(
                    "Something went wrong while loading ride details."
                );

            }
        );

    }
);



// =========================================
// 3. BOOK RIDE
// =========================================

function bookRide() {


    if (!currentRideId) {

        alert(
            "Ride ID not found."
        );

        return;
    }


    var formData =
        new FormData();


    formData.append(
        "ride_id",
        currentRideId
    );


    formData.append(
        "seats",
        1
    );



    // =========================================
    // OPTIONAL TIP
    // =========================================

    var savedTip =
        sessionStorage.getItem(
            "rideNestTip"
        );


    if (
        savedTip !== null &&
        savedTip !== ""
    ) {

        var tip =
            Number(
                savedTip
            );


        if (
            !isNaN(tip) &&
            tip >= 0
        ) {

            formData.append(
                "tip_amount",
                tip
            );

        }

    }



    // =========================================
    // SEND BOOKING
    // =========================================

    fetch(
        "book_ride.php",
        {

            method: "POST",

            body: formData

        }
    )


    .then(
        function (response) {

            return response.json();

        }
    )


    .then(
        function (data) {

            if (data.success) {


                // Remove saved tip
                sessionStorage.removeItem(
                    "rideNestTip"
                );


                alert(
                    data.message
                );


                window.location.href =
                    "My Rides.html";

            }

            else {

                alert(
                    data.message
                );

            }

        }
    )


    .catch(
        function (error) {

            console.error(
                "Booking error:",
                error
            );


            alert(
                "Something went wrong while booking."
            );

        }
    );

}