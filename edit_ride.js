document.addEventListener("DOMContentLoaded", function () {

    const params = new URLSearchParams(window.location.search);
    const rideId = params.get("id");

    const form = document.getElementById("editRideForm");
    const message = document.getElementById("formMessage");
    const saveBtn = document.getElementById("saveBtn");
    const seatHint = document.getElementById("seatHint");

    if (!rideId) {
        showMessage("No ride was selected.");
        form.style.display = "none";
        return;
    }

    document.getElementById("rideId").value = rideId;

    loadRide(rideId);

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        saveRide();
    });

    function loadRide(id) {

        fetch("get_ride.php?id=" + encodeURIComponent(id))

            .then(function (response) {
                return response.json();
            })

            .then(function (data) {

                if (!data.success) {
                    showMessage(data.message || "Unable to load ride.");
                    form.style.display = "none";
                    return;
                }

                const ride = data.ride;

                document.getElementById("pickup").value =
                    ride.from_location || "";

                document.getElementById("destination").value =
                    ride.to_location || "";

                document.getElementById("pickupCheckpoint").value =
                    ride.pickup_checkpoint || "";

                document.getElementById("dropCheckpoint").value =
                    ride.drop_checkpoint || "";

                document.getElementById("date").value =
                    ride.ride_date || "";

                document.getElementById("time").value =
                    String(ride.ride_time || "").substring(0, 5);

                const bookedSeats =
                    Number(ride.booked_seats || 0);

                const availableSeats =
                    Number(ride.available_seats || 0);

                const totalSeats =
                    bookedSeats + availableSeats;

                document.getElementById("seats").value =
                    totalSeats;

                document.getElementById("price").value =
                    ride.price || 0;

                document.getElementById("vehicle").value =
                    ride.vehicle_number || "";

                /*
                 * If vehicle_number is not part of the ride row,
                 * load it from the driver's registered vehicle.
                 */
                if (!ride.vehicle_number) {
                    loadDriverVehicle();
                }

                document.getElementById("femaleOnly").checked =
                    Number(ride.female_only) === 1;

                document.getElementById("recurring").checked =
                    Number(ride.recurring) === 1;

                seatHint.textContent =
                    bookedSeats > 0
                        ? bookedSeats + " seat(s) already booked. Total seats cannot go below this."
                        : "No seats are currently booked.";

                window.currentBookedSeats = bookedSeats;

            })

            .catch(function (error) {
                console.error(error);
                showMessage("Unable to load ride details.");
                form.style.display = "none";
            });
    }

    function loadDriverVehicle() {

        /*
         * The current driver vehicle is returned by get_ride.php
         * in the updated backend below. This fallback simply keeps
         * the field untouched if no vehicle value is returned.
         */
    }

    function saveRide() {

        const pickup =
            document.getElementById("pickup").value.trim();

        const destination =
            document.getElementById("destination").value.trim();

        const pickupCheckpoint =
            document.getElementById("pickupCheckpoint").value.trim();

        const dropCheckpoint =
            document.getElementById("dropCheckpoint").value.trim();

        const date =
            document.getElementById("date").value;

        const time =
            document.getElementById("time").value;

        const seats =
            parseInt(document.getElementById("seats").value, 10);

        const price =
            parseFloat(document.getElementById("price").value);

        const vehicle =
            document.getElementById("vehicle").value.trim();

        const femaleOnly =
            document.getElementById("femaleOnly").checked;

        const recurring =
            document.getElementById("recurring").checked;

        if (
            pickup === "" ||
            destination === "" ||
            date === "" ||
            time === "" ||
            vehicle === "" ||
            !Number.isFinite(seats) ||
            seats <= 0 ||
            !Number.isFinite(price) ||
            price < 0
        ) {
            showMessage("Please fill all required fields correctly.");
            return;
        }

        if (
            window.currentBookedSeats !== undefined &&
            seats < window.currentBookedSeats
        ) {
            showMessage(
                "Total seats cannot be less than the seats already booked."
            );
            return;
        }

        const rideDateTime =
            new Date(date + "T" + time);

        if (
            Number.isNaN(rideDateTime.getTime()) ||
            rideDateTime <= new Date()
        ) {
            showMessage("Ride date and time must be in the future.");
            return;
        }

        const formData = new FormData();

        formData.append(
            "ride_id",
            document.getElementById("rideId").value
        );

        formData.append("pickup", pickup);
        formData.append("destination", destination);
        formData.append("pickup_checkpoint", pickupCheckpoint);
        formData.append("drop_checkpoint", dropCheckpoint);
        formData.append("date", date);
        formData.append("time", time);
        formData.append("seats", String(seats));
        formData.append("vehicle", vehicle);
        formData.append("price", String(price));
        formData.append(
            "female_only",
            femaleOnly ? "1" : "0"
        );
        formData.append(
            "recurring",
            recurring ? "1" : "0"
        );

        saveBtn.disabled = true;
        saveBtn.querySelector("span:first-child").textContent =
            "Saving...";

        message.textContent = "";

        fetch("update_ride.php", {
            method: "POST",
            body: formData
        })

        .then(function (response) {
            return response.json();
        })

        .then(function (data) {

            if (!data.success) {
                showMessage(data.message || "Ride could not be updated.");
                saveBtn.disabled = false;
                saveBtn.querySelector("span:first-child").textContent =
                    "Save changes";
                return;
            }

            window.location.href = "my_posted_rides.html";

        })

        .catch(function (error) {

            console.error(error);

            showMessage(
                "Something went wrong. Please try again."
            );

            saveBtn.disabled = false;
            saveBtn.querySelector("span:first-child").textContent =
                "Save changes";
        });
    }

    function showMessage(text) {
        message.textContent = text;
    }

});
