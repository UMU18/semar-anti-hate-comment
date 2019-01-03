function doChange(object, comment_id)
    {
        var classChange = document.getElementById('id'+comment_id);
        var xhttp   = new XMLHttpRequest();
        var meta_value = -1;
        var origin = object.innerHTML;
        
 
         if(origin === "Not Safe"){
                    object.innerHTML = "Safe";
                    meta_value = 1;
                    classChange.className = "category1";
                }
                else{
                    object.innerHTML = "Not Safe";
                    meta_value = 0;
                    classChange.className = "category0";
                }
       
        xhttp.onreadystatechange = function() {
            if (xhttp.readyState == 4 && xhttp.status == 200) {
               //alert(http.responseText);
               
            }
        };
       
        xhttp.open("POST", "anti-hate-comment.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("meta_value=" + meta_value + "&comment_id=" + comment_id);
 
    }


