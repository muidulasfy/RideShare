// =====================================================
// DELETE USER
// =====================================================

function deleteUser(id) {

    const answer = confirm(
        "Are you sure you want to delete this user?"
    );

    if (!answer) {
        return;
    }


    fetch(
        "admin_delete_user.php?id=" + encodeURIComponent(id)
    )

        .then(function(response) {

            return response.json();

        })

        .then(function(data) {

            if (data.success) {

                alert("User deleted successfully.");

                location.reload();

            } else {

                alert(
                    data.message ||
                    "Unable to delete user."
                );

            }

        })

        .catch(function(error) {

            console.error(error);

            alert(
                "Something went wrong."
            );

        });

}



// =====================================================
// DELETE RIDE
// =====================================================

function deleteRide(id) {

    const answer = confirm(
        "Are you sure you want to delete this ride?"
    );

    if (!answer) {
        return;
    }


    fetch(
        "admin_delete_ride.php?id=" + encodeURIComponent(id)
    )

        .then(function(response) {

            return response.json();

        })

        .then(function(data) {

            if (data.success) {

                alert(
                    "Ride deleted successfully."
                );

                location.reload();

            } else {

                alert(
                    data.message ||
                    "Unable to delete ride."
                );

            }

        })

        .catch(function(error) {

            console.error(error);

            alert(
                "Something went wrong."
            );

        });

}



// =====================================================
// CURRENT DATE
// =====================================================

function updateDate() {

    const now = new Date();


    const options = {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric"
    };


    const formatted =
        now.toLocaleDateString(
            "en-US",
            options
        );


    const topDate =
        document.getElementById(
            "currentDate"
        );


    const dashboardDate =
        document.getElementById(
            "dashboardDate"
        );


    if (topDate) {

        topDate.textContent =
            formatted;

    }


    if (dashboardDate) {

        dashboardDate.textContent =
            formatted;

    }

}


updateDate();



// =====================================================
// SIDEBAR NAVIGATION
// =====================================================

document
    .querySelectorAll(".menu-item")
    .forEach(function(button) {

        button.addEventListener(
            "click",
            function() {

                const targetId =
                    button.dataset.target;


                const target =
                    document.getElementById(
                        targetId
                    );


                if (!target) {
                    return;
                }


                document
                    .querySelectorAll(
                        ".menu-item"
                    )
                    .forEach(function(item) {

                        item.classList.remove(
                            "active"
                        );

                    });


                button.classList.add(
                    "active"
                );


                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

            }
        );

    });



// =====================================================
// VIEW ALL BUTTON
// =====================================================

document
    .querySelectorAll(
        ".view-all-btn"
    )
    .forEach(function(button) {

        button.addEventListener(
            "click",
            function() {

                const targetId =
                    button.dataset.target;


                const target =
                    document.getElementById(
                        targetId
                    );


                if (!target) {
                    return;
                }


                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });


                document
                    .querySelectorAll(
                        ".menu-item"
                    )
                    .forEach(function(item) {

                        item.classList.remove(
                            "active"
                        );

                    });


                const menuButton =
                    document.querySelector(
                        '.menu-item[data-target="' +
                        targetId +
                        '"]'
                    );


                if (menuButton) {

                    menuButton.classList.add(
                        "active"
                    );

                }

            }
        );

    });



// =====================================================
// TABLE SEARCH
// =====================================================

document
    .querySelectorAll(
        ".table-search"
    )
    .forEach(function(searchBox) {

        searchBox.addEventListener(
            "input",
            function() {

                const tableId =
                    searchBox.dataset.table;


                const table =
                    document.getElementById(
                        tableId
                    );


                if (!table) {
                    return;
                }


                const searchText =
                    searchBox.value
                        .toLowerCase()
                        .trim();


                const rows =
                    table.querySelectorAll(
                        "tbody tr"
                    );


                rows.forEach(function(row) {

                    const rowText =
                        row.textContent
                            .toLowerCase();


                    if (
                        rowText.includes(
                            searchText
                        )
                    ) {

                        row.style.display =
                            "";

                    } else {

                        row.style.display =
                            "none";

                    }

                });

            }
        );

    });



// =====================================================
// CSV EXPORT
// =====================================================

document
    .querySelectorAll(
        ".export-btn"
    )
    .forEach(function(button) {

        button.addEventListener(
            "click",
            function() {

                const tableId =
                    button.dataset.table;

                const fileName =
                    button.dataset.name ||
                    "ridenest-data";


                const table =
                    document.getElementById(
                        tableId
                    );


                if (!table) {
                    return;
                }


                const rows =
                    table.querySelectorAll(
                        "tr"
                    );


                const csv = [];


                rows.forEach(function(row) {

                    const cells =
                        row.querySelectorAll(
                            "th, td"
                        );


                    const rowData = [];


                    cells.forEach(
                        function(cell) {

                            let text =
                                cell.innerText
                                    .replace(
                                        /\s+/g,
                                        " "
                                    )
                                    .trim();


                            text =
                                text.replace(
                                    /"/g,
                                    '""'
                                );


                            rowData.push(
                                '"' +
                                text +
                                '"'
                            );

                        }
                    );


                    csv.push(
                        rowData.join(",")
                    );

                });


                const blob =
                    new Blob(
                        [
                            csv.join("\n")
                        ],
                        {
                            type:
                                "text/csv;charset=utf-8;"
                        }
                    );


                const url =
                    URL.createObjectURL(
                        blob
                    );


                const link =
                    document.createElement(
                        "a"
                    );


                link.href = url;

                link.download =
                    fileName +
                    "-" +
                    Date.now() +
                    ".csv";


                document
                    .body
                    .appendChild(link);


                link.click();


                link.remove();


                URL.revokeObjectURL(
                    url
                );

            }
        );

    });



// =====================================================
// DARK MODE
// =====================================================

const themeToggle =
    document.getElementById(
        "themeToggle"
    );


const savedTheme =
    localStorage.getItem(
        "ridenestAdminTheme"
    );


if (savedTheme === "dark") {

    document.body.classList.add(
        "dark"
    );

}


function updateThemeButton() {

    if (!themeToggle) {
        return;
    }


    if (
        document.body.classList.contains(
            "dark"
        )
    ) {

        themeToggle.textContent =
            "☀️ Light mode";

    } else {

        themeToggle.textContent =
            "🌙 Dark mode";

    }

}


updateThemeButton();


if (themeToggle) {

    themeToggle.addEventListener(
        "click",
        function() {

            document.body.classList.toggle(
                "dark"
            );


            const isDark =
                document.body.classList.contains(
                    "dark"
                );


            localStorage.setItem(
                "ridenestAdminTheme",
                isDark
                    ? "dark"
                    : "light"
            );


            updateThemeButton();

        }
    );

}



// =====================================================
// UPDATE ACTIVE MENU WHILE SCROLLING
// =====================================================

const sections = [
    document.getElementById("dashboard"),
    document.getElementById("users-section"),
    document.getElementById("rides-section"),
    document.getElementById("bookings-section")
];


window.addEventListener(
    "scroll",
    function() {

        let current =
            "dashboard";


        sections.forEach(
            function(section) {

                if (!section) {
                    return;
                }


                const top =
                    section.getBoundingClientRect()
                        .top;


                if (top <= 160) {

                    current =
                        section.id;

                }

            }
        );


        document
            .querySelectorAll(
                ".menu-item"
            )
            .forEach(
                function(item) {

                    item.classList.remove(
                        "active"
                    );


                    if (
                        item.dataset.target ===
                        current
                    ) {

                        item.classList.add(
                            "active"
                        );

                    }

                }
            );

    }
);