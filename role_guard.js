(function () {

    // ==========================================
    // CURRENT PAGE
    // ==========================================

    const page =
        window.location.pathname
            .split("/")
            .pop()
            .toLowerCase();


    // ==========================================
    // PAGES ONLY STUDENTS CAN USE
    // ==========================================

    const studentPages = [

        "find_ride.html",

        "my_rides.html",

        "my rides.html",

        "my_rides3(female).html",

        "my_rides3(male).html",

        "ride_details.html"

    ];


    // ==========================================
    // PAGES ONLY DRIVERS CAN USE
    // ==========================================

    const driverPages = [

        "post_ride.html",

        "my_posted_rides.html",

        "edit_ride.html"

    ];


    // ==========================================
    // COMMON PAGES
    // ==========================================

    const commonPages = [

        "main_page.html",

        "chat.html",

        "profile.html"

    ];


    // ==========================================
    // ACCESS DENIED SCREEN
    // ==========================================

    function showAccessDenied(message) {

        document.body.innerHTML = `

            <div class="access-denied-page">

                <div class="access-denied-card">

                    <div class="access-icon">
                        🔒
                    </div>

                    <h1>
                        Access Restricted
                    </h1>

                    <p>
                        ${message}
                    </p>

                    <button
                        onclick="history.back()"
                        class="back-button"
                    >
                        ← Go Back
                    </button>

                </div>

            </div>

        `;


        const style =
            document.createElement("style");


        style.textContent = `

            .access-denied-page {

                min-height: 100vh;

                display: flex;

                align-items: center;

                justify-content: center;

                padding: 30px;

                background:
                    linear-gradient(
                        135deg,
                        #f5f9ff,
                        #eef5ff
                    );

                font-family:
                    Arial,
                    sans-serif;

            }


            .access-denied-card {

                width: 100%;

                max-width: 460px;

                text-align: center;

                background: white;

                padding: 45px 35px;

                border-radius: 22px;

                box-shadow:
                    0 20px 50px
                    rgba(20, 70, 130, 0.12);

                border:
                    1px solid #e4edf8;

            }


            .access-icon {

                width: 70px;

                height: 70px;

                margin:
                    0 auto 20px;

                display: flex;

                align-items: center;

                justify-content: center;

                border-radius: 50%;

                background: #eef5ff;

                font-size: 30px;

            }


            .access-denied-card h1 {

                margin: 0 0 12px;

                color: #173b6c;

                font-size: 28px;

            }


            .access-denied-card p {

                margin: 0 auto 28px;

                max-width: 360px;

                line-height: 1.6;

                color: #68778d;

                font-size: 15px;

            }


            .back-button {

                border: none;

                background: #1769e0;

                color: white;

                padding: 12px 24px;

                border-radius: 10px;

                font-size: 14px;

                cursor: pointer;

                transition: 0.25s ease;

            }


            .back-button:hover {

                background: #0e56bf;

                transform:
                    translateY(-2px);

            }

        `;


        document.head.appendChild(style);

    }


    // ==========================================
    // HIDE FORBIDDEN NAVBAR LINKS
    // ==========================================

    function setupNavbar(role) {

        const links =
            document.querySelectorAll(
                "a[href]"
            );


        links.forEach(function (link) {

            const href =
                link
                    .getAttribute("href")
                    .split("?")[0]
                    .split("#")[0]
                    .toLowerCase();


            // Student cannot see driver links

            if (
                role === "user" &&
                (
                    href === "post_ride.html" ||
                    href === "my_posted_rides.html" ||
                    href === "edit_ride.html"
                )
            ) {

                link.style.display = "none";

            }


            // Driver cannot see student links

            if (
                role === "driver" &&
                (
                    href === "find_ride.html" ||
                    href === "my_rides.html" ||
                    href === "my rides.html" ||
                    href === "my_rides3(female).html" ||
                    href === "my_rides3(male).html" ||
                    href === "ride_details.html"
                )
            ) {

                link.style.display = "none";

            }

        });

    }


    // ==========================================
    // CHECK ACCESS
    // ==========================================

    fetch("role_access.php")

        .then(function (response) {

            return response.json();

        })

        .then(function (data) {

            // ------------------------------
            // NOT LOGGED IN
            // ------------------------------

            if (!data.loggedIn) {

                window.location.href =
                    "login.html";

                return;

            }


            const role =
                String(data.role || "")
                    .trim()
                    .toLowerCase();


            // ------------------------------
            // NAVBAR
            // ------------------------------

            setupNavbar(role);


            // ------------------------------
            // DRIVER ON STUDENT PAGE
            // ------------------------------

            if (
                role === "driver" &&
                studentPages.includes(page)
            ) {

                showAccessDenied(

                    "This page is available for students only."
                );

                return;

            }


            // ------------------------------
            // STUDENT ON DRIVER PAGE
            // ------------------------------

            if (
                role === "user" &&
                driverPages.includes(page)
            ) {

                showAccessDenied(

                    "This page is available for drivers only."
                );

                return;

            }


            // ------------------------------
            // INVALID ROLE
            // ------------------------------

            if (
                role !== "user" &&
                role !== "driver"
            ) {

                showAccessDenied(

                    "Your account role could not be verified."
                );

                return;

            }

        })

        .catch(function (error) {

            console.log(
                "Role check error:",
                error
            );

        });

})();