function deleteRide(button) {

    var answer = confirm("Do you want to delete this ride?");

    if (answer == true) {
        button.parentElement.remove();
    }

}