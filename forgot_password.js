var resetButton =
    document.getElementById("resetButton");


resetButton.onclick = function () {


    // =========================================
    // GET VALUES
    // =========================================

    var email =
        document.getElementById("email")
        .value
        .trim();


    var newPassword =
        document.getElementById("newPassword")
        .value;


    var confirmPassword =
        document.getElementById("confirmPassword")
        .value;


    // =========================================
    // EMPTY CHECK
    // =========================================

    if (
        email === "" ||
        newPassword === "" ||
        confirmPassword === ""
    ) {

        alert(
            "Please fill in all fields."
        );

        return;
    }


    // =========================================
    // PASSWORD LENGTH
    // =========================================

    if (newPassword.length < 6) {

        alert(
            "Password must be at least 6 characters."
        );

        return;
    }


    // =========================================
    // PASSWORD MATCH
    // =========================================

    if (
        newPassword !== confirmPassword
    ) {

        alert(
            "Passwords do not match."
        );

        return;
    }


    // =========================================
    // CREATE FORM DATA
    // =========================================

    var formData =
        new FormData();


    formData.append(
        "email",
        email
    );


    formData.append(
        "new_password",
        newPassword
    );


    // =========================================
    // SEND REQUEST
    // =========================================

    fetch(
        "forgot_password.php",
        {

            method: "POST",

            body: formData

        }
    )

    .then(
        function(response) {

            return response.json();

        }
    )

    .then(
        function(data) {


            // =================================
            // SUCCESS
            // =================================

            if (data.success) {

                alert(
                    data.message
                );


                window.location.href =
                    "login.html";

                return;
            }


            // =================================
            // ERROR
            // =================================

            alert(
                data.message
            );

        }
    )

    .catch(
        function(error) {

            console.log(error);

            alert(
                "Something went wrong. Please try again."
            );

        }
    );

};