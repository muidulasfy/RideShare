

function showStudent() {

    document.getElementById("studentForm").style.display = "block";
    document.getElementById("driverForm").style.display = "none";

    document.getElementById("studentBtn").classList.add("active");
    document.getElementById("driverBtn").classList.remove("active");
}


function showDriver() {

    document.getElementById("studentForm").style.display = "none";
    document.getElementById("driverForm").style.display = "block";

    document.getElementById("studentBtn").classList.remove("active");
    document.getElementById("driverBtn").classList.add("active");
}


function registerStudent() {

    var name = document.getElementById("name").value.trim();
    var department = document.getElementById("department").value.trim();
    var phone = document.getElementById("phone").value.trim();
    var password = document.getElementById("password").value;
    var confirm = document.getElementById("confirm").value;
    var studentId = document.getElementById("student").value.trim();
    var email = document.getElementById("email").value.trim();

    var genderElement = document.querySelector(
        'input[name="gender"]:checked'
    );


    if (
        name === "" ||
        department === "" ||
        phone === "" ||
        password === "" ||
        confirm === "" ||
        studentId === "" ||
        email === ""
    ) {
        alert("Please fill in all fields.");
        return;
    }

    if (genderElement === null) {
        alert("Please select your gender.");
        return;
    }



    if (password !== confirm) {
        alert("Passwords do not match.");
        return;
    }


    if (password.length < 6) {
        alert("Password must be at least 6 characters.");
        return;
    }


    var gender = genderElement.value;


    var formData = new FormData();

    formData.append("name", name);
    formData.append("email", email);
    formData.append("phone", phone);
    formData.append("department", department);
    formData.append("student_id", studentId);
    formData.append("password", password);
    formData.append("gender", gender);
    formData.append("role", "user");

    fetch("register.php", {
        method: "POST",
        body: formData
    })

    .then(function(response) {
        return response.json();
    })

    .then(function(data) {

        if (data.success) {

            alert(data.message);

            window.location.href = "login.html";

        } else {

            alert(data.message);

        }

    })

    .catch(function(error) {

        console.log(error);

        alert("Server error. Please try again.");

    });
}


function registerDriver() {

    var name = document.getElementById("driverName").value.trim();
    var email = document.getElementById("driverEmail").value.trim();
    var phone = document.getElementById("driverPhone").value.trim();
    var license = document.getElementById("license").value.trim();
    var vehicle = document.getElementById("vehicle").value.trim();
    var model = document.getElementById("model").value.trim();
    var vehicleType = document.getElementById("vehicleType").value;
    var password = document.getElementById("driverPassword").value;
    var confirm = document.getElementById("driverConfirm").value;

    var genderElement = document.querySelector(
        'input[name="driverGender"]:checked'
    );


  if (
    name === "" ||
    email === "" ||
    phone === "" ||
    license === "" ||
    vehicle === "" ||
    vehicleType === "" ||
    model === "" ||
    password === "" ||
    confirm === ""
) {
    alert("Please fill in all fields.");
    return;
}


    if (genderElement === null) {
        alert("Please select your gender.");
        return;
    }


    if (password !== confirm) {
        alert("Passwords do not match.");
        return;
    }


    if (password.length < 6) {
        alert("Password must be at least 6 characters.");
        return;
    }


    var gender = genderElement.value;


    var formData = new FormData();

    formData.append("name", name);
    formData.append("email", email);
    formData.append("phone", phone);
    formData.append("license", license);
    formData.append("vehicle", vehicle);
    formData.append("vehicleType", vehicleType);
    formData.append("model", model);
    formData.append("password", password);
    formData.append("gender", gender);
    formData.append("role", "driver");


    fetch("register.php", {
        method: "POST",
        body: formData
    })

    .then(function(response) {
        return response.json();
    })

    .then(function(data) {

        if (data.success) {

            alert(data.message);

            window.location.href = "login.html";

        } else {

            alert(data.message);

        }

    })

    .catch(function(error) {

        console.log(error);

        alert("Server error. Please try again.");

    });
}

