// =========================================
// 1. Recurring Ride Autofill
// =========================================

var recurringCheckbox =
    document.getElementById("recurring");


if (recurringCheckbox) {

    recurringCheckbox.addEventListener(
        "change",
        function () {

            // Only load previous data
            // when checkbox is selected

            if (!this.checked) {
                return;
            }


            // Prevent multiple clicks
            this.disabled = true;


            fetch("get_recurring_ride.php")

                .then(function (response) {

                    if (!response.ok) {

                        throw new Error(
                            "Server error"
                        );

                    }

                    return response.json();

                })

                .then(function (data) {

                    if (!data.success) {

                        alert(data.message);

                        return;
                    }


                    var ride = data.ride;


                    // =====================================
                    // Fill previous recurring ride data
                    // =====================================

                    document.getElementById("pickup").value =
                        ride.from_location || "";


                    document.getElementById("destination").value =
                        ride.to_location || "";


                    document.getElementById("pickupCheckpoint").value =
                        ride.pickup_checkpoint || "";


                    document.getElementById("dropCheckpoint").value =
                        ride.drop_checkpoint || "";


                    document.getElementById("time").value =
                        ride.ride_time || "";


                    document.getElementById("seats").value =
                        ride.available_seats || "";


                    document.getElementById("vehicle").value =
                        ride.vehicle_type || "";


                    document.getElementById("femaleOnly").checked =
                        Number(ride.female_only) === 1;


                    // If price field exists
                    // fill it automatically

                    var priceInput =
                        document.getElementById("price");


                    if (
                        priceInput &&
                        ride.price !== null &&
                        ride.price !== undefined
                    ) {

                        priceInput.value =
                            ride.price;

                    }


                    // IMPORTANT:
                    // Date is intentionally NOT filled.
                    //
                    // Driver must choose a new date
                    // for the new recurring ride.


                })

                .catch(function (error) {

                    console.log(error);

                    alert(
                        "Could not load previous recurring ride."
                    );

                })

                .finally(function () {

                    recurringCheckbox.disabled = false;

                });

        }
    );

}



// =========================================
// 2. Form Submit
// =========================================

document.getElementById("rideForm").onsubmit =
    function (event) {

        event.preventDefault();


        // =========================================
        // Get values
        // =========================================

        var pickup =
            document.getElementById("pickup")
                .value
                .trim();


        var destination =
            document.getElementById("destination")
                .value
                .trim();


        var pickupCheckpoint =
            document.getElementById("pickupCheckpoint")
                .value
                .trim();


        var dropCheckpoint =
            document.getElementById("dropCheckpoint")
                .value
                .trim();


        var date =
            document.getElementById("date")
                .value;


        var time =
            document.getElementById("time")
                .value;


        var seats =
            document.getElementById("seats")
                .value;


        var vehicle =
            document.getElementById("vehicle")
                .value
                .trim();


        var femaleOnly =
            document.getElementById("femaleOnly")
                .checked;


        var recurring =
            document.getElementById("recurring")
                .checked;



        // =========================================
        // Check required fields
        // =========================================

        if (
            pickup === "" ||
            destination === "" ||
            date === "" ||
            time === "" ||
            seats === "" ||
            vehicle === ""
        ) {

            alert(
                "Please fill all the required fields."
            );

            return;
        }



        // =========================================
        // Check seats
        // =========================================

        if (parseInt(seats) <= 0) {

            alert(
                "Available seats must be greater than 0."
            );

            return;
        }



        // =========================================
        // Create FormData
        // =========================================

        var formData = new FormData();


        formData.append(
            "pickup",
            pickup
        );


        formData.append(
            "destination",
            destination
        );


        formData.append(
            "pickup_checkpoint",
            pickupCheckpoint
        );


        formData.append(
            "drop_checkpoint",
            dropCheckpoint
        );


        formData.append(
            "date",
            date
        );


        formData.append(
            "time",
            time
        );


        formData.append(
            "seats",
            seats
        );


        formData.append(
            "vehicle",
            vehicle
        );


        formData.append(
            "female_only",
            femaleOnly ? "1" : "0"
        );


        formData.append(
            "recurring",
            recurring ? "1" : "0"
        );


        // =========================================
        // Price
        // =========================================

        var priceInput =
            document.getElementById("price");


        if (priceInput) {

            formData.append(
                "price",
                priceInput.value
            );

        }



        // =========================================
        // Send data to PHP
        // =========================================

        fetch(
            "post_ride.php",
            {
                method: "POST",
                body: formData
            }
        )

        .then(function (response) {

            return response.json();

        })

        .then(function (data) {

            if (data.success) {

                alert(data.message);


                window.location.href =
                    "my_posted_rides.html";

            }

            else {

                alert(data.message);

            }

        })

        .catch(function (error) {

            console.log(error);

            alert(
                "Something went wrong. Please try again."
            );

        });

    };