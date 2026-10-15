/* =========================================
   RIDENEST - PROFILE
========================================= */


document.addEventListener(
    "DOMContentLoaded",
    function () {

        loadProfile();

    }
);



// =========================================
// LOAD PROFILE
// =========================================

function loadProfile() {

    fetch("profile.php")

        .then(function (response) {

            return response.json();

        })

        .then(function (data) {

            if (!data.success) {

                alert(
                    data.message ||
                    "Unable to load profile."
                );

                return;

            }


            var profile =
                data.profile ||
                data.user ||
                {};


            // =====================================
            // BASIC PROFILE
            // =====================================

            var name =
                profile.name ||
                "User";


            setText(
                "profileName",
                name
            );


            setText(
                "profilePhone",
                profile.phone ||
                "Phone not available"
            );


            setText(
                "profileDepartment",
                profile.department ||
                "United International University"
            );


            // =====================================
            // INITIAL
            // =====================================

            var initial =
                name
                    .trim()
                    .charAt(0)
                    .toUpperCase();


            setText(
                "userInitial",
                initial || "U"
            );


            // =====================================
            // ROLE
            // =====================================

            var role =
                String(
                    profile.role ||
                    "user"
                )
                .toLowerCase();


            var roleBadge =
                document.getElementById(
                    "roleBadge"
                );


            if (roleBadge) {

                if (role === "driver") {

                    roleBadge.textContent =
                        "Driver";

                }

                else {

                    roleBadge.textContent =
                        "Student";

                }

            }


            // =====================================
            // STATS
            // =====================================

            setText(
                "availableRides",
                Number(
                    profile.available_rides ||
                    0
                )
            );


            setText(
                "totalBookings",
                Number(
                    profile.total_bookings ||
                    0
                )
            );


            setText(
                "completedTrips",
                Number(
                    profile.completed_trips ||
                    0
                )
            );


            // =====================================
            // RATING
            // =====================================

            var rating =
                Number(
                    profile.average_rating ||
                    0
                );


            var ratingCount =
                Number(
                    profile.rating_count ||
                    0
                );


            var ratingElement =
                document.getElementById(
                    "myRating"
                );


            if (ratingElement) {

                if (
                    rating > 0
                ) {

                    ratingElement.textContent =
                        rating.toFixed(1) +
                        " ★";

                }

                else {

                    ratingElement.textContent =
                        "—";

                }

            }


            var ratingCountElement =
                document.getElementById(
                    "ratingCount"
                );


            if (ratingCountElement) {

                if (
                    ratingCount > 0
                ) {

                    ratingCountElement.textContent =
                        ratingCount +
                        (
                            ratingCount === 1
                                ? " rating"
                                : " ratings"
                        );

                }

                else {

                    ratingCountElement.textContent =
                        "No ratings yet";

                }

            }


            // =====================================
            // DRIVER INFORMATION
            // =====================================

            var driverSection =
                document.getElementById(
                    "driverSection"
                );


            if (
                role === "driver" &&
                driverSection
            ) {

                driverSection.style.display =
                    "block";


                setText(
                    "vehicleType",
                    profile.vehicle_type ||
                    "Not available"
                );


                setText(
                    "vehicleModel",
                    profile.vehicle_model ||
                    "Not available"
                );


                setText(
                    "vehicleNumber",
                    profile.vehicle_number ||
                    "Not available"
                );


                setText(
                    "driverCompletedRides",
                    Number(
                        profile.completed_rides ||
                        profile.completed_trips ||
                        0
                    )
                );


                var ownProfile =
                    document.getElementById(
                        "viewOwnDriverProfile"
                    );


                if (ownProfile && profile.id) {

                    ownProfile.href =
                        "driver_profile.html?id=" +
                        encodeURIComponent(
                            profile.id
                        );

                }

            }


            // =====================================
            // RECENT ACTIVITY
            // =====================================

            renderActivities(
                data.activities ||
                profile.activities ||
                []
            );

        })

        .catch(function (error) {

            console.error(
                "Profile error:",
                error
            );


            var activity =
                document.getElementById(
                    "activityContainer"
                );


            if (activity) {

                activity.innerHTML = `
                    <div class="activity-loading">
                        Unable to load recent activity.
                    </div>
                `;

            }

        });

}



// =========================================
// RENDER ACTIVITIES
// =========================================

function renderActivities(
    activities
) {

    var container =
        document.getElementById(
            "activityContainer"
        );


    if (!container) {

        return;

    }


    if (
        !activities ||
        activities.length === 0
    ) {

        container.innerHTML = `
            <div class="activity-loading">
                No recent activity yet.
            </div>
        `;

        return;

    }


    container.innerHTML =
        "";


    activities
        .slice(0, 5)
        .forEach(
            function (activity) {


                var item =
                    document.createElement(
                        "div"
                    );


                item.className =
                    "activity-item";


                var icon =
                    document.createElement(
                        "div"
                    );


                icon.className =
                    "activity-icon";


                icon.textContent =
                    activity.icon ||
                    "🚗";


                var info =
                    document.createElement(
                        "div"
                    );


                info.className =
                    "activity-info";


                var title =
                    document.createElement(
                        "h4"
                    );


                title.textContent =
                    activity.title ||
                    "Ride activity";


                var description =
                    document.createElement(
                        "p"
                    );


                description.textContent =
                    activity.description ||
                    "RideNest activity";


                info.appendChild(
                    title
                );

                info.appendChild(
                    description
                );


                var status =
                    document.createElement(
                        "span"
                    );


                status.className =
                    "activity-status";


                status.textContent =
                    activity.status ||
                    "Completed";


                item.appendChild(
                    icon
                );

                item.appendChild(
                    info
                );

                item.appendChild(
                    status
                );


                container.appendChild(
                    item
                );

            }
        );

}



// =========================================
// HELPER
// =========================================

function setText(
    id,
    value
) {

    var element =
        document.getElementById(
            id
        );


    if (element) {

        element.textContent =
            value;

    }

}