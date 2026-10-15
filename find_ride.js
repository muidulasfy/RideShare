/* =========================================================
   RIDENEST FIND RIDE
   ========================================================= */


/* =========================================================
   GLOBAL LOCATION ARRAYS
   ========================================================= */

let pickupLocations = [];

let destinationLocations = [];



/* =========================================================
   GET LOCATIONS FROM URL
   ========================================================= */

function getLocationsFromURL() {


    const params =
        new URLSearchParams(
            window.location.search
        );


    const pickupParams =
        params.getAll("pickup");


    const destinationParams =
        params.getAll("destination");



    /* =========================================
       PICKUP
       ========================================= */

    pickupLocations =
        pickupParams
            .map(
                function (location) {

                    return location.trim();

                }
            )
            .filter(
                function (location) {

                    return location !== "";

                }
            );



    if (
        pickupLocations.length === 0
    ) {

        pickupLocations = [""];

    }



    /* =========================================
       DESTINATION
       ========================================= */

    destinationLocations =
        destinationParams
            .map(
                function (location) {

                    return location.trim();

                }
            )
            .filter(
                function (location) {

                    return location !== "";

                }
            );



    if (
        destinationLocations.length === 0
    ) {

        destinationLocations = [""];

    }

}



/* =========================================================
   RENDER LOCATION INPUTS
   ========================================================= */

function renderLocationInputs() {


    const pickupList =
        document.getElementById(
            "pickupList"
        );


    const destinationList =
        document.getElementById(
            "destinationList"
        );



    if (
        !pickupList ||
        !destinationList
    ) {

        console.error(
            "Location lists not found."
        );

        return;

    }



    pickupList.innerHTML = "";

    destinationList.innerHTML = "";



    /* =====================================================
       PICKUP INPUTS
       ===================================================== */

    pickupLocations.forEach(
        function (location, index) {


            const row =
                document.createElement(
                    "div"
                );


            row.className =
                "location-row";



            const input =
                document.createElement(
                    "input"
                );


            input.type =
                "text";


            input.className =
                "location-input";


            input.placeholder =
                "Enter pickup location";


            input.value =
                location;


            input.autocomplete =
                "off";



            input.addEventListener(
                "input",
                function () {

                    pickupLocations[index] =
                        this.value;

                }
            );



            row.appendChild(
                input
            );



            /* REMOVE BUTTON */

            if (
                pickupLocations.length > 1
            ) {


                const removeButton =
                    document.createElement(
                        "button"
                    );


                removeButton.type =
                    "button";


                removeButton.className =
                    "remove-location";


                removeButton.innerHTML =
                    "×";


                removeButton.addEventListener(
                    "click",
                    function () {


                        pickupLocations.splice(
                            index,
                            1
                        );


                        renderLocationInputs();

                    }
                );


                row.appendChild(
                    removeButton
                );

            }



            pickupList.appendChild(
                row
            );

        }
    );



    /* =====================================================
       DESTINATION INPUTS
       ===================================================== */

    destinationLocations.forEach(
        function (location, index) {


            const row =
                document.createElement(
                    "div"
                );


            row.className =
                "location-row";



            const input =
                document.createElement(
                    "input"
                );


            input.type =
                "text";


            input.className =
                "location-input";


            input.placeholder =
                "Enter destination";


            input.value =
                location;


            input.autocomplete =
                "off";



            input.addEventListener(
                "input",
                function () {

                    destinationLocations[index] =
                        this.value;

                }
            );



            row.appendChild(
                input
            );



            /* REMOVE BUTTON */

            if (
                destinationLocations.length > 1
            ) {


                const removeButton =
                    document.createElement(
                        "button"
                    );


                removeButton.type =
                    "button";


                removeButton.className =
                    "remove-location";


                removeButton.innerHTML =
                    "×";


                removeButton.addEventListener(
                    "click",
                    function () {


                        destinationLocations.splice(
                            index,
                            1
                        );


                        renderLocationInputs();

                    }
                );


                row.appendChild(
                    removeButton
                );

            }



            destinationList.appendChild(
                row
            );

        }
    );

}



/* =========================================================
   ADD PICKUP
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const addPickup =
            document.getElementById(
                "addPickup"
            );


        if (addPickup) {


            addPickup.addEventListener(
                "click",
                function () {


                    pickupLocations.push(
                        ""
                    );


                    renderLocationInputs();

                }
            );

        }

    }
);



/* =========================================================
   ADD DESTINATION
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const addDestination =
            document.getElementById(
                "addDestination"
            );


        if (addDestination) {


            addDestination.addEventListener(
                "click",
                function () {


                    destinationLocations.push(
                        ""
                    );


                    renderLocationInputs();

                }
            );

        }

    }
);



/* =========================================================
   LOAD RIDES
   ========================================================= */

function loadRides(
    pickups = [],
    destinations = []
) {


    const ridesContainer =
        document.getElementById(
            "ridesContainer"
        );


    const rideCount =
        document.getElementById(
            "rideCount"
        );



    if (!ridesContainer) {

        return;

    }



    ridesContainer.innerHTML = `

        <div class="loading">

            Loading rides...

        </div>

    `;



    const params =
        new URLSearchParams();



    /* =========================================
       PICKUPS
       ========================================= */

    pickups.forEach(
        function (pickup) {


            if (
                pickup.trim() !== ""
            ) {


                params.append(
                    "pickup[]",
                    pickup.trim()
                );

            }

        }
    );



    /* =========================================
       DESTINATIONS
       ========================================= */

    destinations.forEach(
        function (destination) {


            if (
                destination.trim() !== ""
            ) {


                params.append(
                    "destination[]",
                    destination.trim()
                );

            }

        }
    );



    /* =========================================
       OTHER FILTERS
       ========================================= */

    const pickupTime =
        document.getElementById(
            "pickupTime"
        );


    const passengers =
        document.getElementById(
            "passengers"
        );



    if (
        pickupTime &&
        pickupTime.value
    ) {


        params.append(
            "pickup_time",
            pickupTime.value
        );

    }



    if (
        passengers &&
        passengers.value
    ) {


        params.append(
            "passengers",
            passengers.value
        );

    }



    /* =========================================
       FETCH PHP
       ========================================= */

    fetch(
        "find_ride.php?"
        + params.toString()
    )


        .then(
            function (response) {


                if (!response.ok) {

                    throw new Error(
                        "Server error"
                    );

                }


                return response.json();

            }
        )


        .then(
            function (data) {


                if (
                    !data.success
                ) {


                    ridesContainer.innerHTML = `

                        <div class="no-rides">

                            Unable to load rides.

                        </div>

                    `;


                    if (rideCount) {

                        rideCount.textContent =
                            "";

                    }


                    return;

                }



                const rides =
                    data.rides || [];



                if (rideCount) {


                    rideCount.textContent =
                        rides.length
                        + " ride"
                        + (
                            rides.length !== 1
                                ? "s"
                                : ""
                        );

                }



                /* =================================
                   NO RIDES
                   ================================= */

                if (
                    rides.length === 0
                ) {


                    ridesContainer.innerHTML = `

                        <div class="no-rides">

                            No rides found for the selected
                            pickup and destination.

                        </div>

                    `;


                    return;

                }



                /* =================================
                   CLEAR
                   ================================= */

                ridesContainer.innerHTML =
                    "";



                /* =================================
                   DISPLAY RIDES
                   ================================= */

                rides.forEach(
                    function (ride) {


                        let vehicleIcon =
                            "🚗";


                        const vehicle =
                            (
                                ride.vehicle_type
                                || ""
                            )
                                .toLowerCase();



                        if (
                            vehicle.includes(
                                "bike"
                            )
                            ||
                            vehicle.includes(
                                "motorcycle"
                            )
                        ) {

                            vehicleIcon =
                                "🏍️";

                        }

                        else if (
                            vehicle.includes(
                                "cng"
                            )
                            ||
                            vehicle.includes(
                                "auto"
                            )
                        ) {

                            vehicleIcon =
                                "🛺";

                        }

                        else if (
                            vehicle.includes(
                                "bus"
                            )
                        ) {

                            vehicleIcon =
                                "🚌";

                        }

                        else if (
                            vehicle.includes(
                                "hiace"
                            )
                            ||
                            vehicle.includes(
                                "van"
                            )
                        ) {

                            vehicleIcon =
                                "🚐";

                        }



                        const femaleTag =
                            Number(
                                ride.female_only
                            ) === 1

                                ?

                                `

                            <div class="female-tag">

                                Female Only

                            </div>

                            `

                                :

                                "";



                        const card =
                            document.createElement(
                                "div"
                            );


                        card.className =
                            "ride-card";



                        card.innerHTML = `

                            <div class="ride-top">


                                <div class="driver-info">


                                    <div class="driver-avatar">

                                        👤

                                    </div>


                                    <div>


                                        <div class="driver-name">

                                            ${escapeHTML(
                            ride.driver_name
                            || "Driver"
                        )}

                                        </div>


                                        ${femaleTag}


                                    </div>


                                </div>


                                <div class="vehicle">

                                    ${vehicleIcon}

                                </div>


                            </div>



                            <div class="route">


                                <div class="route-row">


                                    <span class="route-dot"></span>


                                    <span>

                                        ${escapeHTML(
                            ride.from_location
                            || ""
                        )}

                                    </span>


                                </div>



                                <div class="route-line"></div>



                                <div class="route-row">


                                    <span class="route-dot"></span>


                                    <span>

                                        ${escapeHTML(
                            ride.to_location
                            || ""
                        )}

                                    </span>


                                </div>


                            </div>



                            <div class="ride-bottom">


                                <div class="ride-details">


                                    <span>

                                        📅
                                        ${escapeHTML(
                            ride.ride_date
                            || ""
                        )}

                                    </span>


                                    <span>

                                        🕐
                                        ${escapeHTML(
                            ride.ride_time
                            || ""
                        )}

                                    </span>


                                    <span>

                                        👥
                                        ${escapeHTML(
                            String(
                                ride.available_seats
                                || ""
                            )
                        )}

                                    </span>


                                </div>



                                <div class="ride-price">

                                    ৳
                                    ${escapeHTML(
                            String(
                                ride.price_per_seat
                                || "0"
                            )
                        )}

                                </div>


                            </div>

                        `;



                        /* =================================
                           CARD CLICK
                           ================================= */
                        card.addEventListener(
                            "click",
                            function () {

                                if (!ride.id) {
                                    return;
                                }


                                /*
                                 * Get selected tip
                                 */

                                const selectedTip =
                                    document.querySelector(
                                        'input[name="tipAmount"]:checked'
                                    );


                                let tipAmount = 0;


                                if (selectedTip) {

                                    if (selectedTip.value === "custom") {

                                        const customTipInput =
                                            document.getElementById(
                                                "customTipAmount"
                                            );


                                        tipAmount =
                                            parseFloat(
                                                customTipInput
                                                    ? customTipInput.value
                                                    : 0
                                            );


                                        if (
                                            isNaN(tipAmount) ||
                                            tipAmount < 1
                                        ) {

                                            alert(
                                                "Please enter a valid tip amount."
                                            );

                                            return;
                                        }


                                        if (tipAmount > 10000) {

                                            alert(
                                                "Tip amount cannot exceed ৳10,000."
                                            );

                                            return;
                                        }

                                    }

                                    else {

                                        tipAmount =
                                            parseFloat(
                                                selectedTip.value
                                            );

                                    }

                                }


                                /*
                                 * Save selected tip temporarily
                                 * until booking is completed.
                                 */

                                sessionStorage.setItem(
                                    "rideNestTip",
                                    tipAmount.toFixed(2)
                                );


                                /*
                                 * Go to Ride Details
                                 */

                                window.location.href =
                                    "ride_details.html?id="
                                    +
                                    encodeURIComponent(
                                        ride.id
                                    );

                            }
                        );


                        ridesContainer.appendChild(
                            card
                        );

                    }
                );

            }
        )


        .catch(
            function (error) {


                console.error(
                    "Load rides error:",
                    error
                );


                ridesContainer.innerHTML = `

                    <div class="no-rides">

                        Something went wrong while loading rides.

                    </div>

                `;


                if (rideCount) {

                    rideCount.textContent =
                        "";

                }

            }
        );

}



/* =========================================================
   SEARCH BUTTON
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const searchBtn =
            document.getElementById(
                "searchBtn"
            );


        if (!searchBtn) {

            return;

        }


        searchBtn.addEventListener(
            "click",
            function () {


                const pickups =
                    pickupLocations
                        .map(
                            function (location) {

                                return location.trim();

                            }
                        )
                        .filter(
                            function (location) {

                                return location !== "";

                            }
                        );


                const destinations =
                    destinationLocations
                        .map(
                            function (location) {

                                return location.trim();

                            }
                        )
                        .filter(
                            function (location) {

                                return location !== "";

                            }
                        );



                if (
                    pickups.length === 0
                ) {


                    alert(
                        "Please enter at least one pickup location."
                    );


                    return;

                }



                if (
                    destinations.length === 0
                ) {


                    alert(
                        "Please enter at least one destination."
                    );


                    return;

                }



                loadRides(
                    pickups,
                    destinations
                );

            }
        );

    }
);



/* =========================================================
   HTML ESCAPE
   ========================================================= */

function escapeHTML(value) {


    return String(value)

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



/* =========================================================
   PAGE LOAD
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =====================================
           STEP 1
           GET URL DATA
           ===================================== */

        getLocationsFromURL();



        /* =====================================
           STEP 2
           SHOW INPUTS
           ===================================== */

        renderLocationInputs();



        /* =====================================
           STEP 3
           GET NON-EMPTY VALUES
           ===================================== */

        const initialPickups =
            pickupLocations
                .filter(
                    function (location) {

                        return (
                            location.trim()
                            !== ""
                        );

                    }
                );


        const initialDestinations =
            destinationLocations
                .filter(
                    function (location) {

                        return (
                            location.trim()
                            !== ""
                        );

                    }
                );



        /* =====================================
           STEP 4
           AUTO SEARCH
           ===================================== */

        if (
            initialPickups.length > 0
            &&
            initialDestinations.length > 0
        ) {


            loadRides(
                initialPickups,
                initialDestinations
            );

        }

    }
);
/* =========================================================
   EXTRA TIP HANDLER
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const tipRadios =
            document.querySelectorAll(
                'input[name="tipAmount"]'
            );


        const customTipBox =
            document.getElementById(
                "customTipBox"
            );


        const customTipInput =
            document.getElementById(
                "customTipAmount"
            );


        if (!tipRadios.length) {
            return;
        }


        tipRadios.forEach(
            function (radio) {

                radio.addEventListener(
                    "change",
                    function () {

                        if (
                            this.value === "custom"
                        ) {

                            customTipBox.style.display =
                                "flex";

                            customTipInput.focus();

                        }

                        else {

                            customTipBox.style.display =
                                "none";

                            customTipInput.value =
                                "";

                        }

                    }
                );

            }
        );

    }
);