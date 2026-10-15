var button = document.getElementById("loginButton");


button.onclick = function () {

    var email =
        document.getElementById("email").value.trim();

    var password =
        document.getElementById("password").value;


    // Check empty input
    if (email === "" || password === "") {

        alert("Please enter email and password");

        return;
    }


    // Create form data
    var formData = new FormData();

    formData.append("email", email);
    formData.append("password", password);


    // Send login request
    fetch("login.php", {

        method: "POST",
        body: formData

    })

    .then(function (response) {

        return response.json();

    })

    .then(function (data) {

        if (data.success) {

            var role =
                String(data.role || "")
                .trim()
                .toLowerCase();


            var gender =
                String(data.gender || "")
                .trim()
                .toLowerCase();


            // =========================================
            // DRIVER
            // =========================================

            if (role === "driver") {

                window.location.href =
                    "main_page.html";

                return;
            }


            // =========================================
            // FEMALE STUDENT
            // =========================================

if (
    role === "user" &&
    gender === "female"
) {
    window.location.href =
        "my_rides3(female).html";

    return;
}

            // =========================================
            // MALE STUDENT
            // =========================================
if (
    role === "user" &&
    gender === "male"
) {
    window.location.href =
        "my_rides3(male).html";

    return;
}

            // =========================================
            // INVALID USER INFORMATION
            // =========================================

            alert(
                "Invalid user information.\n\n" +
                "Role: " + role +
                "\nGender: " + gender
            );

        }


        else {

            alert(data.message);

        }

    })


    .catch(function (error) {

        console.log(error);

        alert(
            "Something went wrong while logging in."
        );

    });

};