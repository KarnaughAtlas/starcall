function rqFilterRequests() {
  // Declare variables
  var input, filter, table, tr, td, i;
  input = document.getElementById("rqSearchBox");
  filter = input.value.toUpperCase();
  table = document.getElementById("rqListRequests");

  function rqFilterRequests(str) {

        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("txtHint").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET","rq_get_requests.php?q="+str,true);
        xmlhttp.send();
    }
}
