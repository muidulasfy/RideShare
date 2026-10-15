document.addEventListener(
    "DOMContentLoaded",
    function () {

        // ==========================================
        // FIND PROFILE ICON
        // ==========================================

        var profileIcon =
            document.querySelector(
                ".profile, .profile-icon"
            );


        // If this page has no profile icon
        // stop here

        if (!profileIcon) {

            return;

        }


        // ==========================================
        // CREATE DROPDOWN
        // ==========================================

        var dropdown =
            document.createElement("div");


        dropdown.className =
            "profile-dropdown";


        dropdown.innerHTML = `

            <a href="profile.html">
                Profile
            </a>

            <a
                href="logout.php"
                class="signout"
            >
                Sign out
            </a>

        `;


        // ==========================================
        // ADD DROPDOWN TO PROFILE ICON
        // ==========================================

        profileIcon.appendChild(
            dropdown
        );


        // ==========================================
        // PROFILE ICON CLICK
        // ==========================================

        profileIcon.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                dropdown.classList.toggle(
                    "show"
                );

            }
        );


        // ==========================================
        // CLICK OUTSIDE
        // ==========================================

        document.addEventListener(
            "click",
            function () {

                dropdown.classList.remove(
                    "show"
                );

            }
        );

    }
);