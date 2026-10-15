var selectedUserId = null;
var users = [];
var messageTimer;


document.addEventListener("DOMContentLoaded", function() {

    loadUsers();

    document.getElementById("text").addEventListener("keydown", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            sendMessage();
        }
    });

    document.getElementById("searchUser").addEventListener("input", function() {
        searchUsers(this.value);
    });

});


function loadUsers() {

    fetch("get_chat.php")
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {

            users = data;
            showUsers(users);

            var userId = new URLSearchParams(window.location.search).get("user_id");

            if (userId) {

                var user = users.find(function(u) {
                    return String(u.id) === String(userId);
                });

                if (user) {
                    selectUser(user);
                }

            } else if (users.length > 0) {

                selectUser(users[0]);

            }

        })
        .catch(function(error) {

            console.log(error);

            document.getElementById("peopleList").innerHTML =
                "<p>Unable to load users.</p>";

        });

}


function showUsers(list) {

    var peopleList = document.getElementById("peopleList");

    peopleList.innerHTML = "";

    if (list.length === 0) {

        peopleList.innerHTML =
            "<p style='padding:15px;color:#64748b;'>No users found.</p>";

        return;
    }


    list.forEach(function(user) {

        var person = document.createElement("div");

        person.className = "person";

        person.setAttribute("data-user-id", user.id);


        var firstLetter = user.name.charAt(0).toUpperCase();

        person.innerHTML = `
            <div class="person-avatar">
                ${firstLetter}
            </div>

            <div class="person-info">
                <b>${escapeHTML(user.name)}</b>
                <p>${user.role === "driver" ? "Driver" : "Student"}</p>
            </div>
        `;


        person.onclick = function() {
            selectUser(user);
        };


        peopleList.appendChild(person);

    });

}


function selectUser(user) {

    selectedUserId = user.id;

    document.getElementById("name").textContent = user.name;
    document.getElementById("status").textContent = "● Online";

    document.querySelector(".chat-user-icon").textContent =
        user.name.charAt(0).toUpperCase();


    document.querySelectorAll(".person").forEach(function(person) {
        person.classList.remove("selected");
    });


    var selected = document.querySelector(
        '.person[data-user-id="' + user.id + '"]'
    );

    if (selected) {
        selected.classList.add("selected");
    }


    loadMessages();


    if (messageTimer) {
        clearInterval(messageTimer);
    }

    messageTimer = setInterval(loadMessages, 3000);

}


function searchUsers(text) {

    text = text.toLowerCase().trim();

    var filtered = users.filter(function(user) {

        return user.name.toLowerCase().includes(text);

    });

    showUsers(filtered);

}


function loadMessages() {

    if (!selectedUserId) {
        return;
    }


    fetch("get_message.php?receiver_id=" + selectedUserId)

        .then(function(response) {
            return response.json();
        })

        .then(function(messages) {

            var messageBox = document.getElementById("messages");

            messageBox.innerHTML = "";


            if (messages.length === 0) {

                messageBox.innerHTML = `
                    <div class="empty-chat">
                        <div class="empty-icon">💬</div>
                        <h3>Start a conversation</h3>
                        <p>Send a message to start chatting.</p>
                    </div>
                `;

                return;
            }


            messages.forEach(function(msg) {

                var message = document.createElement("div");

                if (Number(msg.sender_id) === Number(selectedUserId)) {

                    message.className = "message driver";

                    message.innerHTML = `
                        <b>${escapeHTML(msg.sender_name)}</b>
                        <p>${escapeHTML(msg.message)}</p>
                    `;

                } else {

                    message.className = "message you";

                    message.innerHTML = `
                        <b>You</b>
                        <p>${escapeHTML(msg.message)}</p>
                    `;

                }

                messageBox.appendChild(message);

            });


            messageBox.scrollTop = messageBox.scrollHeight;

        })

        .catch(function(error) {

            console.log("Message error:", error);

        });

}


function sendMessage() {

    var input = document.getElementById("text");

    var message = input.value.trim();


    if (!message) {
        return;
    }


    if (!selectedUserId) {

        alert("Please select a person first.");

        return;
    }


    var formData = new FormData();

    formData.append("receiver_id", selectedUserId);
    formData.append("message", message);


    fetch("send_message.php", {
        method: "POST",
        body: formData
    })

    .then(function(response) {
        return response.json();
    })

    .then(function(data) {

        if (data.success) {

            input.value = "";
            loadMessages();

        } else {

            alert(data.message);

        }

    })

    .catch(function(error) {

        console.log("Send error:", error);

        alert("Unable to send message.");

    });

}


function escapeHTML(text) {

    var div = document.createElement("div");

    div.textContent = text;

    return div.innerHTML;

}